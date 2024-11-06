<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use Pgvector\Laravel\Vector;

class GetSearchVector
{
    /**
     * Get vector for search query.
     *
     * @param mixed $query String to be vectorized or Vector instance
     * @return \Pgvector\Laravel\Vector|null
     */
    public static function handle(mixed $query): ?Vector
    {
        if ($query instanceof Vector) {
            return $query;
        }

        $embeddingModel = config('pgvector-scout.model');
        $embeddingAction = config('pgvector-scout.action');
        return $embeddingAction::handle($query, $embeddingModel);
    }
} 