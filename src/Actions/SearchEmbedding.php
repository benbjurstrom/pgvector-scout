<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Builder;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

// use Illuminate\Support\Facades\DB;

class SearchEmbedding
{
    /**
     * Search for embeddings using vector similarity
     *
     * @param  Builder<Model>  $builder
     * @return \Illuminate\Database\Eloquent\Builder<Embedding>
     */
    public static function handle(
        Builder $builder,
        Vector $searchVector
    ) {
        $model = $builder->model;
        $query = (new Embedding)
            ->forModel($model)
            ->where('embeddable_type', $model->getMorphClass());

        // Apply __soft_deleted property from the builder
        if (isset($builder->wheres['__soft_deleted'])) {
            $query->where('__soft_deleted', $builder->wheres['__soft_deleted']);
        }

        $query->whereHasMorph('embeddable', [$model->getMorphClass()], function ($query) use ($builder, $model) {
            // When Scout soft delete is enabled, include soft-deleted models in the relationship check
            if (static::usesSoftDelete($model) && config('scout.soft_delete', false)) {
                $query->withTrashed();
            }

            if ($builder->wheres) {
                foreach ($builder->wheres as $key => $value) {
                    // Skip __soft_deleted as it's handled on the embeddings table
                    if ($key === '__soft_deleted') {
                        continue;
                    }
                    $query->where($key, $value);
                }
            }

            if ($builder->whereIns) {
                foreach ($builder->whereIns as $field => $values) {
                    $query->whereIn($field, $values);
                }
            }

            if ($builder->whereNotIns) {
                foreach ($builder->whereNotIns as $field => $values) {
                    $query->whereNotIn($field, $values);
                }
            }
        });

        $query->nearestNeighbors('vector', $searchVector, Distance::Cosine);

        if ($builder->limit) {
            $query->limit($builder->limit);
        }

        //        DB::connection()->enableQueryLog();
        //            $query->get();
        //        dd(DB::getQueryLog());

        return $query;
    }

    /**
     * Determine if model uses soft deletes.
     *
     * @param  Model  $model
     */
    protected static function usesSoftDelete($model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }
}
