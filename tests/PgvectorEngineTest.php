<?php

use BenBjurstrom\PgvectorScout\PgvectorEngine;
use BenBjurstrom\PgvectorScout\Models\Embedding;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\Review;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->engine = new PgvectorEngine();
});

test('delete removes embeddings for given models', function () {
    // Create test models using factory
    $review1 = Review::factory()->create();
    $review2 = Review::factory()->create();

    // Create embeddings for the models using factory
    Embedding::factory()
        ->forModel($review1)
        ->create();

    Embedding::factory()
        ->forModel($review2)
        ->create();

    // Verify embeddings exist
    expect(Embedding::count())->toBe(2);

    // Delete one model's embedding
    $this->engine->delete(new Collection([$review1]));

    // Verify only one embedding was deleted
    expect(Embedding::count())->toBe(1);
    expect(Embedding::where('embeddable_id', $review2->id)->exists())->toBeTrue();
    expect(Embedding::where('embeddable_id', $review1->id)->exists())->toBeFalse();
});

test('delete handles empty collection gracefully', function () {
    $this->engine->delete(new Collection());
    
    expect(true)->toBeTrue(); // Test passes if no exception is thrown
});

test('delete removes multiple embeddings', function () {
    // Create test models using factory
    $reviews = Review::factory()
        ->count(3)
        ->create();

    // Create embeddings for each review using factory
    $reviews->each(function ($review) {
        Embedding::factory()
            ->forModel($review)
            ->create();
    });

    // Verify initial state
    expect(Embedding::count())->toBe(3);

    // Delete all embeddings
    $this->engine->delete($reviews);

    // Verify all embeddings were deleted
    expect(Embedding::count())->toBe(0);
});
