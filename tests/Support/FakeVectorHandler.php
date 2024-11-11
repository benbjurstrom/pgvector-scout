<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support;

use BenBjurstrom\PgvectorScout\Contracts\EmbeddingHandler;
use Pgvector\Laravel\Vector;
use RuntimeException;

class FakeVectorHandler implements EmbeddingHandler
{
    /**
     * Get a Fake vector for a given input
     *
     * @throws RuntimeException
     */
    public static function handle(string $input, string $embeddingModel): Vector
    {
        return new Vector(array_fill(0, 1536, 0.1));
    }
}
