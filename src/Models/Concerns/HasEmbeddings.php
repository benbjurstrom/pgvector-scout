<?php

namespace BenBjurstrom\PgvectorScout\Models\Concerns;

use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasEmbeddings
{
    /**
     * @return MorphOne<Embedding>
     */
    public function embedding(): MorphOne
    {
        return $this->morphOne(Embedding::class, 'embeddable');
    }
}
