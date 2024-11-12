<?php

namespace BenBjurstrom\PgvectorScout\Handlers;

use BenBjurstrom\PgvectorScout\HandlerConfig;
use BenBjurstrom\PgvectorScout\HandlerContract;
use Pgvector\Laravel\Vector;
use RuntimeException;

class FakeHandler implements HandlerContract
{
    /**
     * Get a Fake vector for a given input
     *
     * @throws RuntimeException
     */
    public static function handle(string $input, HandlerConfig $config): Vector
    {
        return new Vector(array_fill(0, $config->dimensions, 0.1));
    }
}
