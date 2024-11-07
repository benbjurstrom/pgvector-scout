<?php

namespace BenBjurstrom\PgvectorScout;

use BenBjurstrom\PgvectorScout\Models\Concerns\EmbeddableModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Contracts\PaginatesEloquentModels;
use Laravel\Scout\Contracts\PaginatesEloquentModelsUsingDatabase;
use Laravel\Scout\Engines\Engine;
use BenBjurstrom\PgvectorScout\Models\Embedding;
use BenBjurstrom\PgvectorScout\Actions\CreateEmbedding;
use BenBjurstrom\PgvectorScout\Actions\SearchEmbedding;
use BenBjurstrom\PgvectorScout\Actions\GetSearchVector;

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

        $models = $models->take(5);

        // Process each model using the CreateEmbedding action
        $models->each(function (EmbeddableModel $model) {
            CreateEmbedding::handle($model);
        });
    }

    /**
     * Perform the given search on the engine.
     *
     * @param Builder $builder
     * @return Collection
     */
    public function search(Builder $builder)
    {
        if (blank($builder->query)) {
            return new Collection([]);
        }

        // Get the search vector using the action class
        $searchVector = GetSearchVector::handle($builder->query);

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

        // Get the search vector using the action class
        $searchVector = GetSearchVector::handle($builder->query);

        // Get search results with pagination parameters
        return SearchEmbedding::handle(
            $builder,
            $searchVector,
            $perPage,
            $page
        );
    }

    public function simplePaginate(Builder $builder, $perPage, $page)
    {
        dd('j');
    }

//    public function paginateUsingDatabase(Builder $builder, $perPage, $pageName, $page)
//    {
//        dd('j');
//    }
//
//    public function simplePaginateUsingDatabase(Builder $builder, $perPage, $pageName, $page)
//    {
//        dd('k');
//    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        dd('yo');
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
        if ($models->isEmpty()) {
            return;
        }

        $models->each(function ($model) {
            Embedding::query()
                ->where('embeddable_type', get_class($model))
                ->where('embeddable_id', $model->getKey())
                ->forceDelete();
        });
    }

    public function mapIds($results)
    {
        dd($results);
        // TODO: Implement mapIds() method.
    }

    public function map(Builder $builder, $results, $model)
    {
        if ($results->isEmpty()) {
            return $model->newCollection();
        }

        // Get all the model IDs
        $modelIds = $results->pluck('embeddable_id');

        // Eager load the actual models
        $models = $model->whereIn($model->getKeyName(), $modelIds)->get()
            ->keyBy(fn ($model) => $model->getKey());

        // Map the embedding models to their corresponding models
        // while setting the embedding relationship
        return $results->map(function ($embedding) use ($models) {
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
        if (! isset($results['results'])) {
            return 0;
        }

        return $results['results']->count();
    }
}
