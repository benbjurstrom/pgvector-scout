<?php

namespace BenBjurstrom\PgvectorScout;

use BenBjurstrom\PgvectorScout\Actions\CreateEmbedding;
use BenBjurstrom\PgvectorScout\Actions\FetchEmbedding;
use BenBjurstrom\PgvectorScout\Actions\SearchEmbedding;
use BenBjurstrom\PgvectorScout\Actions\ValidateSearchable;
use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class PgvectorEngine extends Engine
{
    /**
     * Update the given model in the index.
     *
     * @param  Collection<int, Model>  $models
     * @return void
     */
    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        Log::info('Updating embeddings', [
            'count' => $models->count(),
            'model' => get_class($models->first()),
        ]);

        $config = HandlerConfig::fromConfig();
        $models->each(function (Model $model) use ($config) {
            CreateEmbedding::handle($model, $config);
        });
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder<Model>  $builder
     * @return Collection<int, Embedding>
     */
    public function search(Builder $builder): Collection
    {
        if (blank($builder->query)) {
            return (new Embedding)->newCollection();
        }

        $searchVector = FetchEmbedding::handle($builder->query);

        $query = SearchEmbedding::handle(
            $builder,
            $searchVector
        );

        return $query->get();
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  Builder<Model>  $builder
     * @return LengthAwarePaginator<Embedding>
     */
    public function paginate(Builder $builder, $perPage, $page): LengthAwarePaginator
    {
        if (blank($builder->query)) {
            return (new Embedding)->paginate();
        }

        $searchVector = FetchEmbedding::handle($builder->query);

        $builder->take($perPage);
        $query = SearchEmbedding::handle(
            $builder,
            $searchVector
        );

        return $query->paginate($perPage, pageName: 'page', page: $page);
    }

    /**
     * Create a search index.
     * Not needed since we use a shared vectors table.
     *
     * @param  string  $name
     * @param  array<int, string>  $options
     */
    public function createIndex($name, array $options = [])
    {
        // Not implemented
    }

    /**
     * Delete a search index.
     * Not needed since we use a shared vectors table.
     *
     * @param  string  $name
     */
    public function deleteIndex($name)
    {
        // Not implemented
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  Model  $model
     * @return void
     */
    public function flush($model)
    {
        Embedding::query()
            ->where('embeddable_type', get_class($model))
            ->delete();
    }

    /**
     * Remove the given model from the index.
     *
     * @param  Collection<int, Model>  $models
     * @return void
     */
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

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  Collection<int, Embedding>  $results
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function mapIds($results)
    {
        return $results->pluck('embeddable_id')->values();
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     *
     * @param  Builder<Model>  $builder
     * @param  Collection<int, Embedding>  $results
     * @param  Model  $model
     * @return LazyCollection<int, Model>
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        if ($results->isEmpty()) {
            return LazyCollection::make($model->newCollection());
        }

        return new LazyCollection($this->map($builder, $results, $model));
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  Builder<Model>  $builder
     * @param  Collection<int, Embedding>  $results
     * @param  Model  $model
     * @return Collection<int, Model>
     */
    public function map(Builder $builder, $results, $model)
    {
        if ($results->isEmpty()) {
            return $model->newCollection();
        }

        ValidateSearchable::handle($model);

        $objectIds = $results->pluck('embeddable_id')->toArray();

        $objectIdPositions = array_flip($objectIds);

        $models = $model->getScoutModelsByIds($builder, $objectIds)
            ->filter(static function ($model) use ($objectIds) {
                return in_array($model->getScoutKey(), $objectIds, false);
            })
            ->sortBy(static function ($model) use ($objectIdPositions) {
                return $objectIdPositions[$model->getScoutKey()];
            })
            ->values();

        // Map the embeddings as children of the models
        $models = $models->keyBy(function (Model $model): int|string {
            return $model->getKey();
        });

        return $results->map(function ($embedding) use ($models): ?Model {
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
     * @param  mixed  $results
     */
    public function getTotalCount($results): int
    {
        if ($results instanceof Collection) {
            return $results->count();
        }

        return $results->total();
    }
}
