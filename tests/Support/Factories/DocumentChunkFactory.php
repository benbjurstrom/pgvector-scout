<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support\Factories;

use BenBjurstrom\PgvectorScout\Tests\Support\Models\Document;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\DocumentChunk;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentChunkFactory extends Factory
{
    protected $model = DocumentChunk::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'content' => $this->faker->paragraph,
            'chunk_number' => $this->faker->numberBetween(1, 10),
        ];
    }
}
