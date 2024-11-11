<?php

namespace BenBjurstrom\PgvectorScout;

use BenBjurstrom\PgvectorScout\Actions\CreateEmbedding;
use BenBjurstrom\PgvectorScout\Actions\FetchEmbedding;
use BenBjurstrom\PgvectorScout\Actions\SearchEmbedding;
use BenBjurstrom\PgvectorScout\Models\Concerns\EmbeddableModel;
use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class PgvectorEngine extends Engine
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

        $models->each(function (EmbeddableModel $model) {
            CreateEmbedding::handle($model);
        });
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @return Collection<int, Model>
     */
    public function search(Builder $builder): Collection
    {
        if (blank($builder->query)) {
            return new Collection();
        }

        $searchVector = FetchEmbedding::handle($builder->query);

        return SearchEmbedding::handle(
            $builder,
            $searchVector
        );
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        if (blank($builder->query)) {
            return new Collection([]);
        }

        $searchVector = FetchEmbedding::handle($builder->query);

        return SearchEmbedding::handle(
            $builder,
            $searchVector,
            $perPage,
            $page
        );
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
            ->delete();
    }

    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        // Use a single query instead of multiple queries
        Embedding::query()
            ->where('embeddable_type', get_class($models->first()))
            ->whereIn('embeddable_id', $models->pluck('id'))
            ->delete();
    }

    public function mapIds($results)
    {
        return $results->pluck('embeddable_id')->values();
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        return LazyCollection::make($this->map($builder, $results, $model));
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param Builder $builder
     * @param $results
     * @param $model
     * @return Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($results->isEmpty()) {
            return $model->newCollection();
        }

        $modelIds = $results->pluck('embeddable_id');

        // Eager load the actual models
        $models = $model->whereIn($model->getKeyName(), $modelIds)
            ->get()
            ->keyBy(fn ($model) => $model->getKey());

        // Map the embeddings to the models
        return $results->map(function ($embedding) use ($models) {
            if (isset($models[$embedding->embeddable_id])) {
                $model = $models[$embedding->embeddable_id];
                $model->setRelation('embedding', $embedding);
                return $model;
            }
            return null;
        })->filter();
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param mixed $results
     * @return int
     */
    public function getTotalCount($results): int
    {
        if($results instanceof Collection) {
            return $results->count();
        }

        return $results->total();
    }
}
