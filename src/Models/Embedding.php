<?php

namespace BenBjurstrom\PgvectorScout\Models;

use BenBjurstrom\PgvectorScout\Database\Factories\EmbeddingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

/**
 * @property string|int $embeddable_id
 * @property string $embeddable_type
 * @property string $embedding_model
 * @property string $content_hash
 * @property Vector $vector
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * */
class Embedding extends Model
{
    /** @use HasFactory<EmbeddingFactory> */
    use HasFactory, HasNeighbors;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vector' => Vector::class,
    ];

    /**
     * Get the parent embeddable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }

    public function forModel(Model $model): Embedding
    {
        $index = $model->searchableAs();

        $table = config("pgvector-scout.handlers.{$index}.table");
        $this->setTable($table);

        return $this;
    }

    /**
     * Create a new instance of the given model.
     *
     * @param  array<int, mixed>  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false): Embedding
    {
        $model = parent::newInstance($attributes, $exists);
        $model->setTable($this->table);

        return $model;
    }
}
