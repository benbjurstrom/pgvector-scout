<?php

namespace BenBjurstrom\PgvectorScout;

use BenBjurstrom\PgvectorScout\Actions\GetOpenAiEmbeddings;
use BenBjurstrom\PgvectorScout\Models\Concerns\EmbeddableModel;
use BenBjurstrom\PgvectorScout\Models\Concerns\HasEmbeddings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Searchable;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;
use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class PgvectorEngine extends DatabaseEngine
{
    /**
     * Update the given model in the index.
     *
     * @param Collection<int, EmbeddableModel> $models
     * @return void
     */
    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        // Handle soft deletes if configured
        if ($this->usesSoftDelete($models->first()) && config('scout.soft_delete', false)) {
            $models->each->pushSoftDeleteMetadata();
        }

        // Process each model
        $models->each(function (EmbeddableModel $model) {

            // Get the searchable data
            $searchableData = $model->toSearchableArray();
            if (empty($searchableData)) {
                return;
            }

            // Merge with scout metadata
            $data = array_merge(
                $searchableData,
                $model->scoutMetadata(),
            );

            // Calculate hash of the content to determine if we need to update
            $contentHash = Uuid::uuid5(Uuid::NAMESPACE_OID, json_encode($data));
            $embeddingModel = config('pgvector-scout.model');

            // Check if we already have a vector for this model with the same hash
            $existingVector = Embedding::query()
                ->where('embeddable_type', get_class($model))
                ->where('embeddable_id', $model->getKey())
                ->where('content_hash', $contentHash)
                ->where('embedding_model', $embeddingModel)
                ->first();

            // If we have a matching vector, no need to update
            if ($existingVector) {
                return;
            }

            $embeddingAction = config('pgvector-scout.action');
            $vector = $embeddingAction::handle(json_encode($data), $embeddingModel);

            // Create or update the embedding
            Embedding::updateOrCreate(
                [
                    'embeddable_type' => get_class($model),
                    'embeddable_id' => $model->getKey(),
                ],
                [
                    'embedding_model' => $embeddingModel,
                    'content_hash' => $contentHash,
                    'embedding' => $vector
                ]
            );
        });
    }

    /**
     * Determine if model uses soft deletes.
     *
     * @param  Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        // If no query is provided, fallback to standard database search
        if (empty($builder->query)) {
            return parent::search($builder);
        }

        // Get the search vector - either directly provided or generated from string
        $searchVector = $this->getSearchVector($builder->query);

        if (!$searchVector) {
            return ['results' => Collection::make(), 'total' => 0];
        }

        // Get the model's query builder
        $query = $builder->model->newQuery();

        // Apply any query constraints from the Scout builder
        $query = $this->applyScoutConstraints($builder, $query);

        // Perform vector similarity search
        $query->nearestNeighbors(
            'vector',
            $searchVector,
            $builder->options['distance'] ?? Distance::Cosine
        );

        // Apply limit if specified
        if ($builder->limit) {
            $query->take($builder->limit);
        }

        $models = $query->get();

        return [
            'results' => $models,
            'total' => $models->count(),
        ];
    }

    /**
     * Apply Scout builder constraints to the query.
     */
    protected function applyScoutConstraints(Builder $builder, $query)
    {
        // Handle soft deletes
        $query = $this->constrainForSoftDeletes($builder, $query);

        // Add where clauses
        return $this->addAdditionalConstraints($builder, $query);
    }

    /**
     * Get vector for search query.
     *
     * @param mixed $query String to be vectorized or Vector instance
     * @return \Pgvector\Laravel\Vector|null
     */
    protected function getSearchVector(mixed $query): ?Vector
    {
        if ($query instanceof Vector) {
            return $query;
        }

        // Cache the vectorized string to avoid repeated API calls
        return cache()->remember(
            'vector_search_'.md5($query),
            now()->addDay(),
            fn() => $this->vectorizeString($query)
        );
    }

    /**
     * Convert a string to a vector using the configured model.
     */
    protected function vectorizeString(string $query): Vector
    {
        // This will be implemented in the SearchVector job class
        // For now just return a placeholder vector
        return new Vector(array_fill(0, 1536, 0.0));
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        return LazyCollection::make($results['results']->all());
    }

    /**
     * Create a search index.
     * Not needed since we use a shared vectors table.
     */
    public function createIndex($name, array $options = [])
    {
        // Not implemented
    }

    /**
     * Delete a search index.
     * Not needed since we use a shared vectors table.
     */
    public function deleteIndex($name)
    {
        // Not implemented
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param Model $model
     * @return void
     */
    public function flush($model)
    {
        Embedding::query()
            ->where('embeddable_type', get_class($model))
            ->forceDelete();
    }

    public function delete($models)
    {
        // TODO: Implement delete() method.
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        // TODO: Implement paginate() method.
    }

    public function mapIds($results)
    {
        // TODO: Implement mapIds() method.
    }

    public function map(Builder $builder, $results, $model)
    {
        // TODO: Implement map() method.
    }

    public function getTotalCount($results)
    {
        // TODO: Implement getTotalCount() method.
    }
}
