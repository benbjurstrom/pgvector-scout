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

        $query->whereHas('embeddable', function ($query) use ($builder, $model) {

            if ($builder->wheres) {
                foreach ($builder->wheres as $key => $value) {
                    $query->where($key, $value);
                }
            }

            if (static::usesSoftDelete($model)) {
                $query->whereNull('deleted_at');
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
