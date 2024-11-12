<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support\Models;

use BenBjurstrom\PgvectorScout\Models\Concerns\HasEmbeddings;
use BenBjurstrom\PgvectorScout\Tests\Support\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Review extends Model
{
    use HasEmbeddings, HasFactory, Searchable;

    protected static function newFactory(): Factory
    {
        return ReviewFactory::new();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'summary' => $this->summary,
            'text' => $this->text,
        ];
    }
}
