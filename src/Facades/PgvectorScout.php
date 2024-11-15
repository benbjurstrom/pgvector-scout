<?php

namespace BenBjurstrom\PgvectorScout\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \BenBjurstrom\PgvectorScout\PgvectorScout
 */
class PgvectorScout extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BenBjurstrom\PgvectorScout\PgvectorScout::class;
    }
}
