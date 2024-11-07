<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support\Models;

use BenBjurstrom\PgvectorScout\Tests\Support\Factories\ReviewFactory;
use BenBjurstrom\PgvectorScout\Tests\Support\Factories\ReviewSoftDeleteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewSoftDelete extends Review
{
    use SoftDeletes, HasFactory;

    public $table = 'reviews';

    protected static function newFactory(): Factory
    {
        return ReviewSoftDeleteFactory::new();
    }
}
