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
     * @return Collection
     */
    public static function handle(
        Builder $builder,
        Vector $searchVector,
        ?int $perPage = null,
        ?int $page = null
    ) {
        $query = static::buildQuery($builder->model, $searchVector);

        // Always join with the model's table to support where clauses
        $query->join($builder->model->getTable(), function ($join) use ($builder) {
            $join->on('embeddings.embeddable_id', '=', $builder->model->getTable() . '.id');
        });

        // Apply where conditions to the model's table
        if ($builder->wheres) {
            foreach ($builder->wheres as $key => $value) {
                $query->where($builder->model->getTable() . '.' . $key, $value);
            }
        }

        // Select only the embeddings columns to avoid conflicts
        $query->select('embeddings.*');

        if ($perPage) {
            $skip = ($page - 1) * $perPage;
            $query->skip($skip)->take($perPage);
        }

        return $query->get();
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

        // Handle soft deletes
        if (static::usesSoftDelete($model) && config('scout.soft_delete', false)) {
            $query->join($model->getTable(), function ($join) use ($model) {
                $join->on('embeddings.embeddable_id', '=', $model->getTable() . '.id')
                     ->whereNull($model->getTable() . '.deleted_at');
            });

            // Select only embeddings columns when we've joined for soft deletes
            $query->select('embeddings.*');
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
