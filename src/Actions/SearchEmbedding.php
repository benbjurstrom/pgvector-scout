<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Builder;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

class SearchEmbedding
{
    /**
     * Search for embeddings using vector similarity
     *
     * @param Builder $builder
     * @param Vector $searchVector
     * @param ?int $perPage
     * @param ?int $page
     * @return array{results: Collection, total: int}
     */
    public static function handle(
        Builder $builder,
        Vector $searchVector,
        ?int $perPage = null,
        ?int $page = null
    ) {
        $query = static::buildQuery($builder->model, $searchVector);

        if ($builder->wheres) {
            $query->where($builder->wheres);
        }

        if ($perPage) {
            $skip = ($page - 1) * $perPage;
            $query->skip($skip)->take($perPage);
        }

        $results = $query->get();

        return [
            'results' => $results,
        ];
    }

    /**
     * Build the search query
     *
     * @param Model $model
     * @param Vector $searchVector
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function buildQuery(Model $model, Vector $searchVector): \Illuminate\Database\Eloquent\Builder
    {
        $query = Embedding::query()
            ->where('embeddable_type', get_class($model));

        $usesSoftDelete = static::usesSoftDelete($model);

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

    /**
     * Determine if model uses soft deletes.
     *
     * @param  Model  $model
     * @return bool
     */
    protected static function usesSoftDelete($model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }
}
