<?php

namespace BenBjurstrom\PgvectorScout\Models\Concerns;

use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasEmbeddings
{
    /**
     * @return MorphOne<Embedding, $this>
     */
    public function embedding(): MorphOne
    {
        return $this->morphOne(Embedding::class, 'embeddable');
    }

    /**
     * Create a new model instance for a related model.
     *
     * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TRelatedModel>  $class
     * @return TRelatedModel
     */
    protected function newRelatedInstance($class)
    {
        $instance = tap(new $class, function ($instance) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($this->connection);
            }
        });

        if (method_exists($instance, 'forModel')) {
            return $instance->forModel($this);
        }

        return $instance;
    }
}
