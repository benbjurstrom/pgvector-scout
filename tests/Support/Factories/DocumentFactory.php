<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support\Factories;

use BenBjurstrom\PgvectorScout\Tests\Support\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'user_id' => $this->faker->uuid,
            'title' => $this->faker->sentence,
            'status' => $this->faker->randomElement(['active', 'draft', 'archived']),
        ];
    }
}
