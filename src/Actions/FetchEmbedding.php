<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use BenBjurstrom\PgvectorScout\IndexConfig;
use Pgvector\Laravel\Vector;
use RuntimeException;

class FetchEmbedding
{
    /**
     * Get vector for search query.
     *
     * @param  string|Vector  $query  String to be vectorized or Vector instance
     *
     * @throws RuntimeException|\Exception If the handler class is not properly configured
     */
    public static function handle(string|Vector $query, string $index): Vector
    {
        // If the query is already a vector, return it
        if ($query instanceof Vector) {
            return $query;
        }

        $config = IndexConfig::from($index);

        return $config->handler::handle($query, $config);
    }
}
