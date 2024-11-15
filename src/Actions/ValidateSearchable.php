<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class ValidateSearchable
{
    public static function handle(Model &$model): void
    {
        $message = 'Model '.get_class($model).' does not implement the Searchable trait.';

        if (! in_array(Searchable::class, class_uses_recursive($model::class))) {
            throw new \Exception($message);
        }
    }
}
