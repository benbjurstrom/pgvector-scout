<?php

namespace BenBjurstrom\PgvectorScout;

use Pgvector\Laravel\Vector;

interface HandlerContract
{
    /**
     * Generate an embedding vector for the given input
     *
     * @param  string  $input  The text to generate an embedding for
     * @param  HandlerConfig  $config  The handler configuration
     * @return Vector The generated embedding vector
     *
     * @throws \RuntimeException If the embedding generation fails
     */
    public static function handle(string $input, HandlerConfig $config): Vector;
}
