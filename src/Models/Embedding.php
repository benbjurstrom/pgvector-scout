<?php

namespace BenBjurstrom\PgvectorScout\Models;

use BenBjurstrom\PgvectorScout\IndexConfig;
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
 * @property int $__soft_deleted
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * */
class Embedding extends Model
{
    use HasNeighbors;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'embeddable_id',
        'embeddable_type',
        'embedding_model',
        'content_hash',
        'vector',
        '__soft_deleted',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'vector' => Vector::class,
        '__soft_deleted' => 'boolean',
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
        if (! method_exists($model, 'searchableAs')) {
            throw new \RuntimeException('Model '.get_class($model).' does not implement the Searchable trait.');
        }

        $index = $model->searchableAs();

        return $this->forIndex($index);
    }

    public function forIndex(string $index): Embedding
    {
        $config = IndexConfig::from($index);

        $this->setTable($config->table);

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
