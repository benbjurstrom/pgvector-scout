<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

class SearchEmbedding
{
    /**
     * Search for embeddings using vector similarity
     *
     * @param Builder $builder
     * @param Vector|null $searchVector
     * @param bool $usesSoftDelete
     * @return array{results: Collection, total: int}
     */
    public static function handle(Builder $builder, ?Vector $searchVector, bool $usesSoftDelete): array
    {
        if (!$searchVector) {
            return ['results' => Collection::make(), 'total' => 0];
        }

        $query = static::buildQuery($builder->model, $searchVector, $usesSoftDelete);

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
     * Build the search query
     *
     * @param Model $model
     * @param Vector $searchVector
     * @param bool $usesSoftDelete
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function buildQuery(Model $model, Vector $searchVector, bool $usesSoftDelete): \Illuminate\Database\Eloquent\Builder
    {
        $query = Embedding::query()
            ->where('embeddable_type', get_class($model));

        // Handle soft deletes by joining with the parent table
        if ($usesSoftDelete && config('scout.soft_delete', false)) {
            $query->join($model->getTable(), function ($join) use ($model) {
                $join->on('embeddings.embeddable_id', '=', $model->getTable() . '.id')
                     ->whereNull($model->getTable() . '.deleted_at');
            });
        }

        // Apply nearest neighbors search
        return $query->nearestNeighbors('embedding', $searchVector, Distance::Cosine);
    }
} 