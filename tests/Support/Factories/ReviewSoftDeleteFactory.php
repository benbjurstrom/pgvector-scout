<?php

namespace BenBjurstrom\PgvectorScout\Tests\Support\Factories;

use BenBjurstrom\PgvectorScout\Tests\Support\Models\Review;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\ReviewSoftDelete;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewSoftDeleteFactory extends ReviewFactory
{
    protected $model = ReviewSoftDelete::class;
}
