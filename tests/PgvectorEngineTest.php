<?php

use BenBjurstrom\PgvectorScout\Models\Embedding;
use BenBjurstrom\PgvectorScout\PgvectorEngine;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\Review;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\ReviewSoftDelete;
use Illuminate\Database\Eloquent\Collection;
use Pgvector\Laravel\Vector;

beforeEach(function () {
    $this->engine = new PgvectorEngine;
});

test('update method calls CreateEmbedding for each model', function () {
    // Create test models
    Review::factory()
        ->count(2)
        ->create();

    // ensure embeddings are created for all models
    expect(Embedding::count())->toBe(2);
});

test('search method can filter by model properties', function () {
    // Create test models with different scores
    $review1 = Review::factory()->createQuietly(['score' => 5]);
    $review2 = Review::factory()->createQuietly(['score' => 3]);
    $review3 = Review::factory()->createQuietly(['score' => 3]);
    $review4 = Review::factory()->createQuietly(['score' => 1]);

    // Create embeddings for the models using factory
    Embedding::factory()->forModel($review1)->create();
    Embedding::factory()->forModel($review2)->embedding1()->create();
    Embedding::factory()->forModel($review3)->embedding1()->create();
    Embedding::factory()->forModel($review4)->create();

    // Create a Scout builder instance with a search query
    $vector = new Vector(array_fill(0, 1536, 0.1));
    $builder = Review::search($vector);

    // Perform the search
    $results = Review::search($vector)->where('score', 3)->get();

    // Verify the results contain the expected number of items
    expect($results)->toHaveCount(2);

    // Verify the results contain the expected models
    $resultIds = $results->pluck('id')->toArray();
    expect($resultIds)->toContain($review2->id, $review3->id);
    expect($results->first()->embedding->neighbor_distance)->toBeFloat();
});

test('delete method removes embeddings for given models', function () {
    // Create test models using factory
    $review1 = Review::factory()->createQuietly();
    $review2 = Review::factory()->createQuietly();

    // Create embeddings for the models using factory
    Embedding::factory()
        ->forModel($review1)
        ->create();

    Embedding::factory()
        ->forModel($review2)
        ->create();

    // Verify embeddings exist
    expect(Embedding::count())->toBe(2);

    $review1->delete();

    // Verify only one embedding was deleted
    expect(Embedding::count())->toBe(1);
    expect(Embedding::where('embeddable_id', $review1->id)->exists())->toBeFalse();
    expect(Embedding::where('embeddable_id', $review2->id)->exists())->toBeTrue();
});

test('delete handles empty collection gracefully', function () {
    $this->engine->delete(new Collection);

    expect(true)->toBeTrue(); // Test passes if no exception is thrown
});

test('delete removes multiple embeddings', function () {
    // Create test models using factory
    $reviews = Review::factory()
        ->count(3)
        ->createQuietly();

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

test('soft deleting a model does not delete its embedding if scout.soft_delete is true', function () {

    config()->set('scout.soft_delete', true);

    // Create test model using factory
    $review = ReviewSoftDelete::factory()->createQuietly();

    // Create embedding for the model
    $embedding = Embedding::factory()
        ->forModel($review)
        ->create();

    // Verify embedding exists
    expect(Embedding::count())->toBe(1);

    // Soft delete the model
    $review->deleteQuietly();

    // Verify the model is soft deleted
    expect($review->trashed())->toBeTrue();

    // Verify the embedding still exists
    expect(Embedding::count())->toBe(1);
    expect(Embedding::first()->id)->toBe($embedding->id);
});

test('paginate returns correct number of results and pagination metadata', function () {
    // Create test models and embeddings
    $reviews = Review::factory()
        ->count(14)
        ->createQuietly();

    $reviews->each(function ($review) {
        Embedding::factory()
            ->forModel($review)
            ->create();
    });

    // Create a Scout builder instance with a search query
    $vector = new Vector(array_fill(0, 1536, 0.0));

    // Test first page
    $results = Review::search($vector)->paginate(5, page: 1);
    expect($results)
        ->toHaveCount(5)
        ->and($results->currentPage())->toBe(1)
        ->and($results->total())->toBe(14)
        ->and($results->lastPage())->toBe(3);

    // Test second page
    $results = Review::search($vector)->paginate(5, page: 2);
    expect($results)
        ->toHaveCount(5)
        ->and($results->currentPage())->toBe(2)
        ->and($results->total())->toBe(14)
        ->and($results->hasMorePages())->toBeTrue();

    // Test last page
    $results = Review::search($vector)->paginate(5, page: 3);
    expect($results)
        ->toHaveCount(4)
        ->and($results->currentPage())->toBe(3)
        ->and($results->hasMorePages())->toBeFalse();
});

test('paginate handles empty search query', function () {
    // Create test models without embeddings
    Review::factory()
        ->count(10)
        ->createQuietly();

    // Create a Scout builder instance with an empty query
    $results = Review::search('')->paginate(5, page: 1);
    expect($results)
        ->toHaveCount(0)
        ->and($results->total())->toBe(0)
        ->and($results->lastPage())->toBe(1);
});

test('paginate handles out of range pages', function () {
    // Create test models and embeddings
    $reviews = Review::factory()
        ->count(8)
        ->createQuietly();

    $reviews->each(function ($review) {
        Embedding::factory()
            ->forModel($review)
            ->create();
    });

    $vector = new Vector(array_fill(0, 1536, 0.0));

    // Test page beyond available results
    $results = Review::search($vector)->paginate(5, page: 3);
    expect($results)
        ->toHaveCount(0)
        ->and($results->total())->toBe(8)
        ->and($results->currentPage())->toBe(3)
        ->and($results->lastPage())->toBe(2);
});

test('paginate respects where constraints', function () {
    // Create test models with different ratings
    $reviews = collect([
        Review::factory()->createQuietly(['score' => 5]),
        Review::factory()->createQuietly(['score' => 5]),
        Review::factory()->createQuietly(['score' => 3]),
        Review::factory()->createQuietly(['score' => 3]),
        Review::factory()->createQuietly(['score' => 1]),
    ]);

    // Create embeddings for all reviews
    $reviews->each(function ($review) {
        Embedding::factory()
            ->forModel($review)
            ->create();
    });

    $vector = new Vector(array_fill(0, 1536, 0.0));
    $results = Review::search($vector)
        ->where('score', 5)
        ->paginate(2, page: 1);

    expect($results)
        ->toHaveCount(2)
        ->and($results->all())->each(
            fn ($item) => $item->score->toBe(5)
        );
});

test('flush method removes all embeddings for a given model type', function () {
    // Create test models using factory
    $review1 = Review::factory()->createQuietly();
    $review2 = Review::factory()->createQuietly();

    // Create embeddings for the models using factory
    Embedding::factory()
        ->forModel($review1)
        ->create();

    Embedding::factory()
        ->forModel($review2)
        ->create();

    // Verify embeddings exist
    expect(Embedding::count())->toBe(2);

    (new Review)->removeAllFromSearch();

    // Verify all embeddings were deleted
    expect(Embedding::count())->toBe(0);
});