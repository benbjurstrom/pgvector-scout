<?php

namespace BenBjurstrom\PgvectorScout;

use BenBjurstrom\PgvectorScout\Models\Concerns\EmbeddableModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
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

        $models = $models->take(5);

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

            $data = $this->arrayToLabeledText($data);

            // Calculate hash of the content to determine if we need to update
            $contentHash = Uuid::uuid5(Uuid::NAMESPACE_OID, $data);
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
            $vector = $embeddingAction::handle($data, $embeddingModel);

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

    protected function arrayToLabeledText(array $data): string {
        if(array_is_list($data)){
            return $data[0];
        }

        return collect($data)
            ->map(function ($value, $key) {
                // Use Laravel's data_get() for nested arrays
                if (is_array($value)) {
                    $value = data_get($value, '*');
                }

                // Use Laravel's Str::of() for string manipulation
                return Str::of($key)
                    ->append(': ')
                    ->append(match (true) {
                        is_array($value) => json_encode($value),
                        is_bool($value) => $value ? 'true' : 'false',
                        is_null($value) => 'null',
                        default => $value
                    });
            })
            ->join(PHP_EOL);
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

        $model = $builder->model;
        $query = Embedding::query()
            ->where('embeddable_type', get_class($model))
            ->nearestNeighbors('embedding', $searchVector, Distance::Cosine);

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

        $embeddingModel = config('pgvector-scout.model');
        $embeddingAction = config('pgvector-scout.action');
        return $embeddingAction::handle($query, $embeddingModel);
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
        dd($results);
        // TODO: Implement mapIds() method.
    }

    public function map(Builder $builder, $results, $model)
    {
        // Get the collection of embedding models
        $embeddingModels = $results['results'];

        if ($embeddingModels->isEmpty()) {
            return $model->newCollection();
        }

        // Get all the model IDs
        $modelIds = $embeddingModels->pluck('embeddable_id');

        // Eager load the actual models
        $models = $model->whereIn($model->getKeyName(), $modelIds)->get()
            ->keyBy(fn ($model) => $model->getKey());

        // Map the embedding models to their corresponding models
        // while setting the embedding relationship
        return $embeddingModels->map(function ($embedding) use ($models) {
            if (isset($models[$embedding->embeddable_id])) {
                $model = $models[$embedding->embeddable_id];
                $model->setRelation('embedding', $embedding);
                return $model;
            }
            return null;
        })->filter();
    }

    public function getTotalCount($results)
    {
        // TODO: Implement getTotalCount() method.
    }
}
