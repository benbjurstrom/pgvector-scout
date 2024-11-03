<?php

namespace BenBjurstrom\PgvectorScout\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

abstract class EmbeddableModel extends Model
{
    use HasEmbeddings, Searchable;
}
