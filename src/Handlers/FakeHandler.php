<?php

namespace BenBjurstrom\PgvectorScout\Handlers;

use BenBjurstrom\PgvectorScout\HandlerContract;
use BenBjurstrom\PgvectorScout\IndexConfig;
use Pgvector\Laravel\Vector;
use RuntimeException;

class FakeHandler implements HandlerContract
{
    /**
     * Get a Fake vector for a given input
     *
     * @throws RuntimeException
     */
    public static function handle(string $input, IndexConfig $config): Vector
    {
        return new Vector(array_fill(0, $config->dimensions, 0.1));
    }
}
