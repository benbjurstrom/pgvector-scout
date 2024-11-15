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
 * @property Vector $embedding
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
        'embedding' => Vector::class,
    ];

    /**
     * Get the configured table name for the current default handler
     */
    protected function getTableName(): string
    {
        $default = config('pgvector-scout.default');

        return config("pgvector-scout.handlers.{$default}.table", 'embeddings');
    }

    /**
     * Get the parent embeddable model.
     *
     * @return MorphTo<Model, $this>
     */
    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Calculate the content hash for a given string.
     */
    public static function calculateHash(string $content): string
    {
        return md5($content);
    }
}
