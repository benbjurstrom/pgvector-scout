<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use BenBjurstrom\PgvectorScout\HandlerConfig;
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
    public static function handle(string|Vector $query): Vector
    {
        // If the query is already a vector, return it
        if ($query instanceof Vector) {
            return $query;
        }

        $config = HandlerConfig::fromConfig();

        return $config->class::handle($query, $config);
    }
}
