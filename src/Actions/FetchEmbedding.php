<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use BenBjurstrom\PgvectorScout\IndexConfig;
use Pgvector\Laravel\Vector;

class FetchEmbedding
{
    public static function handle(string|Vector $query, IndexConfig $config): Vector
    {
        // If the query is already a vector, return it
        if ($query instanceof Vector) {
            return $query;
        }

        return $config->handler::handle($query, $config);
    }
}
