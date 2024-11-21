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

    protected function newRelatedInstance($class)
    {
        $instance = tap(new $class, function ($instance) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($this->connection);
            }
        });

        return $instance->forModel($this);
    }
}
