<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support\Models;

use BenBjurstrom\PgvectorScout\Models\Concerns\HasEmbeddings;
use BenBjurstrom\PgvectorScout\Tests\Support\Factories\DocumentChunkFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class DocumentChunk extends Model
{
    use HasEmbeddings, HasFactory, Searchable;

    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return DocumentChunkFactory::new();
    }

    /**
     * Get the document that owns the chunk.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'content' => $this->content,
        ];
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'fake';
    }
}
