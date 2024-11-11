<?php

namespace BenBjurstrom\PgvectorScout\Contracts;

use Pgvector\Laravel\Vector;

interface EmbeddingHandler
{
    /**
     * Generate an embedding vector for the given input using the specified model
     *
     * @param  string  $input  The text to generate an embedding for
     * @param  string  $embeddingModel  The name/identifier of the embedding model to use
     * @return Vector The generated embedding vector
     *
     * @throws \RuntimeException If the embedding generation fails
     */
    public static function handle(string $input, string $embeddingModel): Vector;
}
