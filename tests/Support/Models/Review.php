<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support\Models;
use BenBjurstrom\PgvectorScout\Models\Concerns\EmbeddableModel;
use BenBjurstrom\PgvectorScout\Tests\Support\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class Review extends EmbeddableModel
{
   use HasFactory;

    protected static function newFactory(): Factory
    {
        return ReviewFactory::new();
    }
}
