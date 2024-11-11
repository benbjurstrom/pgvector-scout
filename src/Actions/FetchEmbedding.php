<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use Pgvector\Laravel\Vector;
use RuntimeException;

class FetchEmbedding
{
    /**
     * Get vector for search query.
     *
     * @param string|Vector $query String to be vectorized or Vector instance
     * @return Vector
     * @throws RuntimeException|\Exception If the handler class is not properly configured
     */
    public static function handle(string|Vector $query): Vector
    {
        // If the query is already a vector, return it
        if ($query instanceof Vector) {
            return $query;
        }

        $embeddingModel = config('pgvector-scout.embedding.model');
        $handlerClass = config('pgvector-scout.embedding.handler');

        if (!class_exists($handlerClass)) {
            throw new RuntimeException(
                "Embedding handler class '{$handlerClass}' does not exist. Check your pgvector-scout config."
            );
        }

        if (!is_subclass_of($handlerClass, \BenBjurstrom\PgvectorScout\Contracts\EmbeddingHandler::class)) {
            throw new RuntimeException(
                "Embedding handler class '{$handlerClass}' must implement EmbeddingHandler interface."
            );
        }

        return $handlerClass::handle($query, $embeddingModel);
    }
}
