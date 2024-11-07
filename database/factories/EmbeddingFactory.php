<?php

namespace BenBjurstrom\PgvectorScout\Database\Factories;

use BenBjurstrom\PgvectorScout\Models\Embedding;
use Illuminate\Database\Eloquent\Factories\Factory;
use Pgvector\Laravel\Vector;

class EmbeddingFactory extends Factory
{
    protected $model = Embedding::class;

    public function definition(): array
    {
        return [
            'embeddable_type' => $this->faker->word,
            'embeddable_id' => $this->faker->randomNumber(),
            'content_hash' => $this->faker->md5,
            'embedding' => new Vector(array_fill(0, 1536, $this->faker->randomFloat(8, 0, 1))),
            'embedding_model' => 'test-model'
        ];
    }

    public function forModel($model): self
    {
        return $this->state(function (array $attributes) use ($model) {
            return [
                'embeddable_type' => get_class($model),
                'embeddable_id' => $model->getKey(),
            ];
        });
    }
}
