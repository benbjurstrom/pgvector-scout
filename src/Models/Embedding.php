<?php

namespace BenBjurstrom\PgvectorScout\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class Embedding extends Model
{
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
        'metadata' => 'array',
        //'content_hash' => 'uuid',
    ];

    /**
     * Get the parent embeddable model.
     */
    public function embeddable()
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
