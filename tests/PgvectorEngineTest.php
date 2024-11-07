<?php

use BenBjurstrom\PgvectorScout\PgvectorEngine;
use BenBjurstrom\PgvectorScout\Models\Embedding;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\Review;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\ReviewSoftDelete;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Pgvector\Laravel\Vector;

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

test('soft deleting a model does not delete its embedding', function () {
    // Create test model using factory
    $review = ReviewSoftDelete::factory()->create();

    // Create embedding for the model
    $embedding = Embedding::factory()
        ->forModel($review)
        ->create();

    // Verify embedding exists
    expect(Embedding::count())->toBe(1);

    // Soft delete the model
    $review->delete();

    // Verify the model is soft deleted
    expect($review->trashed())->toBeTrue();

    // Verify the embedding still exists
    expect(Embedding::count())->toBe(1);
    expect(Embedding::first()->id)->toBe($embedding->id);
});

test('paginate returns correct number of results', function () {
    // Create test models and embeddings
    $reviews = Review::factory()
        ->count(15)
        ->create();

    $reviews->each(function ($review) {
        Embedding::factory()
            ->forModel($review)
            ->create();
    });

    // Create a Scout builder instance with a search query
    $builder = Review::search('test query');

    // Test first page
    $results = $this->engine->paginate($builder, 5, 1);
    expect($results)->toHaveCount(5);

    // Test second page
    $results = $this->engine->paginate($builder, 5, 2);
    expect($results)->toHaveCount(5);

    // Test last page
    $results = $this->engine->paginate($builder, 5, 3);
    expect($results)->toHaveCount(5);
});

test('paginate handles empty search query', function () {
    // Create test models without embeddings
    Review::factory()
        ->count(10)
        ->create();

    // Create a Scout builder instance with an empty query
    $builder = Review::search('');

    $results = $this->engine->paginate($builder, 5, 1);
    expect($results)->toHaveCount(5);
});

test('paginate handles out of range pages', function () {
    // Create test models and embeddings
    $reviews = Review::factory()
        ->count(8)
        ->create();

    $reviews->each(function ($review) {
        Embedding::factory()
            ->forModel($review)
            ->create();
    });

    $vector = new Vector(array_fill(0, 1536, 0.0));

    // Create a Scout builder instance
    $builder = Review::search($vector);

    // Test page beyond available results
    $results = $this->engine->paginate($builder, 5, 3);
    expect($results)->toHaveCount(0);
});

test('paginate respects where constraints', function () {
    // Create test models with different ratings
    $reviews = collect([
        Review::factory()->create(['score' => 5]),
        Review::factory()->create(['score' => 5]),
        Review::factory()->create(['score' => 3]),
        Review::factory()->create(['score' => 3]),
        Review::factory()->create(['score' => 1]),
    ]);

    // Create embeddings for all reviews
    $reviews->each(function ($review) {
        Embedding::factory()
            ->forModel($review)
            ->create();
    });

    $vector = new Vector(array_fill(0, 1536, 0.0));

    // Create a Scout builder instance with a where constraint
    $builder = Review::search($vector)->where('score', 5);

    $results = $this->engine->paginate($builder, 5, 1);
    expect($results)->toHaveCount(2)
        ->each(fn ($result) => expect($result->rating)->toBe(5));
});
