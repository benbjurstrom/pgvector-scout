<?php

use BenBjurstrom\PgvectorScout\IndexConfig;
use BenBjurstrom\PgvectorScout\Models\Embedding;
use BenBjurstrom\PgvectorScout\PgvectorEngine;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\Review;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\ReviewSoftDelete;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Pgvector\Laravel\Vector;
use Ramsey\Uuid\Uuid;

beforeEach(function () {
    // Load the reviews table migration for testing
    $migration = include __DIR__.'/Support/Migrations/2024_11_06_000000_create_reviews_table.php';
    $migration->up();

    // Load the embeddings table migration
    $migration = include __DIR__.'/Support/Migrations/2024_11_06_000000_create_embeddings_table.php';
    $migration->up();
});

function data(Model $model): array
{
    return [
        'embeddable_type' => get_class($model),
        'embeddable_id' => $model->getKey(),
        'content_hash' => Uuid::uuid1(),
        'vector' => new Vector(array_fill(0, 3, 0.1)),
        'embedding_model' => 'test-model',
    ];
}

function embedding(?string $class = null): Embedding
{
    $class = $class ?? Review::class;

    return (new Embedding)->forModel(new $class);
}

test('update method calls CreateEmbedding for each model', function () {
    // Create test models
    Review::factory()
        ->count(2)
        ->create();

    // ensure embeddings are created for all models
    expect(embedding()->count())->toBe(2);
});

test('search method can filter by model properties', function () {
    // Create test models with different scores
    $review1 = Review::factory()->create(['score' => 5]);
    $review2 = Review::factory()->create(['score' => 3]);
    $review3 = Review::factory()->create(['score' => 3]);
    $review4 = Review::factory()->create(['score' => 1]);

    DB::enableQueryLog();
    // Perform the search
    $results = Review::search('test')->where('score', 3)->get();

    // Verify the results contain the expected number of items
    expect($results)->toHaveCount(2);

    // Verify the results contain the expected models
    $resultIds = $results->pluck('id')->toArray();
    expect($resultIds)->toContain($review2->id, $review3->id);
    expect($results->first()->embedding->neighbor_distance)->toBeFloat();

    $queries = DB::getQueryLog();
    expect($queries)->toHaveCount(2);
});

test('search method can order by model properties', function () {
    // Create test models with different scores
    $review4 = Review::factory()->create(['score' => 4]);
    $review1 = Review::factory()->create(['score' => 1]);
    $review2 = Review::factory()->create(['score' => 2]);
    $review7 = Review::factory()->create(['score' => 7]);

    // Perform the search
    $results = Review::search('test')
        ->orderBy('score', 'asc')
        ->get();

    // Verify the results contain the expected number of items
    expect($results)->toHaveCount(4);

    // Verify the results contain the expected models
    $resultIds = $results->pluck('id')->toArray();
    expect($resultIds)->toBe([$review4->id, $review1->id, $review2->id, $review7->id]);
});

test('search method can limit results', function () {
    // Create test models with different scores
    $review1 = Review::factory()->create(['score' => 5]);
    $review2 = Review::factory()->create(['score' => 3]);
    $review3 = Review::factory()->create(['score' => 3]);
    $review4 = Review::factory()->create(['score' => 1]);

    // Perform the search
    $results = Review::search('test')
        ->take(3)
        ->get();

    // Verify the results contain the expected number of items
    expect($results)->toHaveCount(3);
});

test('can search using existing vector', function () {
    $config = IndexConfig::fromModel(new Review);
    $vector = new Vector(array_fill(0, $config->dimensions, 0.1));

    Review::factory()->create(['score' => 5]);
    Review::factory()->create(['score' => 3]);

    $results = Review::search($vector)->get();

    // Verify the results contain the expected number of items
    expect($results)->toHaveCount(2);
});

test('delete method removes embeddings for given models', function () {
    // Create test models using factory
    $review1 = Review::factory()->createQuietly();
    $review2 = Review::factory()->createQuietly();

    // Create embeddings for the models using factory
    embedding()->create(data($review1));

    embedding()->create(data($review2));

    // Verify embeddings exist
    expect(embedding()->count())->toBe(2);

    $review1->delete();

    // Verify only one embedding was deleted
    expect(embedding()->count())->toBe(1);
    expect(embedding()->where('embeddable_id', $review1->id)->exists())->toBeFalse();
    expect(embedding()->where('embeddable_id', $review2->id)->exists())->toBeTrue();
});

test('delete handles empty collection gracefully', function () {
    (new PgvectorEngine)->delete(new Collection);

    expect(true)->toBeTrue(); // Test passes if no exception is thrown
});

test('delete removes multiple embeddings', function () {
    // Create test models using factory
    $reviews = Review::factory()
        ->count(3)
        ->createQuietly();

    // Create embeddings for each review using factory
    $reviews->each(function ($review) {
        embedding()->create(data($review));
    });

    // Verify initial state
    expect(embedding()->count())->toBe(3);

    // Delete all embeddings
    (new PgvectorEngine)->delete($reviews);

    // Verify all embeddings were deleted
    expect(embedding()->count())->toBe(0);
});

test('soft deleting a model does not delete its embedding if scout.soft_delete is true', function () {

    config()->set('scout.soft_delete', true);
    $review = ReviewSoftDelete::factory()->create();
    $embedding = $review->embedding;

    // Verify embedding exists
    expect(embedding()->count())->toBe(1);

    // Soft delete the model
    $review->delete();

    // Verify the model is soft deleted
    expect($review->trashed())->toBeTrue();

    // Verify the embedding still exists with table contains
    $this->assertDatabaseHas($embedding->getTable(), [
        'id' => $embedding->id,
        '__soft_deleted' => true,
    ]);
});

test('soft deleted models excluded from search', function () {
    config()->set('scout.soft_delete', true);

    $deleted = ReviewSoftDelete::factory()->create();

    expect(ReviewSoftDelete::search('test')->get())->toHaveCount(1);

    $deleted->delete();

    $results = ReviewSoftDelete::search('test')->get();
    expect($results)->toHaveCount(0);
});

test('force deleting a model deletes its embedding even if scout.soft_delete is true', function () {

    config()->set('scout.soft_delete', true);
    $review = ReviewSoftDelete::factory()->create();

    // Verify embedding exists
    expect(embedding()->count())->toBe(1);

    // Soft delete the model
    $review->forceDelete();

    // Verify the embedding still exists
    expect(embedding()->count())->toBe(0);
});

test('withTrashed includes soft deleted models in search results', function () {
    config()->set('scout.soft_delete', true);

    $active = ReviewSoftDelete::factory()->create();
    $deleted = ReviewSoftDelete::factory()->create();
    $deleted->delete();

    // Search without withTrashed - should only get active model
    $results = ReviewSoftDelete::search('test')->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($active->id);

    // Search with withTrashed - should get both models
    $results = ReviewSoftDelete::search('test')->withTrashed()->get();
    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->all())->toContain($active->id, $deleted->id);
});

test('onlyTrashed returns only soft deleted models in search results', function () {
    config()->set('scout.soft_delete', true);

    $active = ReviewSoftDelete::factory()->create();
    $deleted = ReviewSoftDelete::factory()->create();
    $deleted->delete();

    $results = ReviewSoftDelete::search('test')->onlyTrashed()->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($deleted->id);
});

test('withTrashed works with where constraints', function () {
    config()->set('scout.soft_delete', true);

    $active = ReviewSoftDelete::factory()->create(['score' => 5]);
    ReviewSoftDelete::factory()->create(['score' => 3]);

    $deleted = ReviewSoftDelete::factory()->create(['score' => 5]);
    $deleted->delete();

    $results = ReviewSoftDelete::search('test')
        ->where('score', 5)
        ->withTrashed()
        ->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->all())->toContain($active->id, $deleted->id);
});

test('onlyTrashed works with where constraints', function () {
    config()->set('scout.soft_delete', true);

    ReviewSoftDelete::factory()->create(['score' => 5]);

    $deleted = ReviewSoftDelete::factory()->create(['score' => 5]);
    $deleted->delete();

    $results = ReviewSoftDelete::search('test')
        ->where('score', 5)
        ->onlyTrashed()
        ->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($deleted->id);
});

test('paginate returns correct number of results and pagination metadata', function () {
    // Create test models and embeddings
    $reviews = Review::factory()
        ->count(14)
        ->createQuietly();

    $reviews->each(function ($review) {
        embedding()->create(data($review));
    });

    // Create a Scout builder instance with a search query

    // Test first page
    $results = Review::search('test')->paginate(5, page: 1);
    expect($results)
        ->toHaveCount(5)
        ->and($results->currentPage())->toBe(1)
        ->and($results->total())->toBe(14)
        ->and($results->lastPage())->toBe(3);

    // Test second page
    $results = Review::search('test')->paginate(5, page: 2);
    expect($results)
        ->toHaveCount(5)
        ->and($results->currentPage())->toBe(2)
        ->and($results->total())->toBe(14)
        ->and($results->hasMorePages())->toBeTrue();

    // Test last page
    $results = Review::search('test')->paginate(5, page: 3);
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
        embedding()->create(data($review));
    });

    // Test page beyond available results
    $results = Review::search('test')->paginate(5, page: 3);
    expect($results)
        ->toHaveCount(0)
        ->and($results->total())->toBe(8)
        ->and($results->currentPage())->toBe(3)
        ->and($results->lastPage())->toBe(2);
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

    $results = Review::search('test')
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
    $review1 = Review::factory()->create();
    $review2 = Review::factory()->create();

    // Verify embeddings exist
    expect(embedding()->count())->toBe(2);

    (new Review)->removeAllFromSearch();

    // Verify all embeddings were deleted
    expect(embedding()->count())->toBe(0);
});

test('cursor returns properly ordered lazy collection of models', function () {
    // Create test models with different scores
    $review1 = Review::factory()->create(['score' => 5]);
    $review2 = Review::factory()->create(['score' => 3]);
    $review3 = Review::factory()->create(['score' => 1]);

    $results = Review::search('test')->cursor();

    expect($results)
        ->toBeInstanceOf(LazyCollection::class)
        ->and($results->count())->toBe(3);
});

test('cursor handles empty search results', function () {
    // Create models but don't create any embeddings
    Review::factory()->count(3)->create();

    $results = Review::search('')->cursor();

    expect($results)
        ->toBeInstanceOf(LazyCollection::class)
        ->and($results->count())->toBe(0);
});

test('cursor respects where constraints', function () {
    // Create test models with different scores
    $review1 = Review::factory()->create(['score' => 5]);
    $review2 = Review::factory()->create(['score' => 5]);
    $review3 = Review::factory()->create(['score' => 3]);

    $results = Review::search('test')
        ->where('score', 5)
        ->cursor();

    expect($results->first()->embedding->neighbor_distance)->toBeFloat();

    expect($results)
        ->toBeInstanceOf(LazyCollection::class)
        ->and($results->count())->toBe(2)
        ->and($results->every(fn ($item) => $item->score === 5))->toBeTrue();
});

test('keys method returns collection of model ids in correct order', function () {
    // Create test models with different scores
    $review1 = Review::factory()->create(['score' => 5]);
    $review2 = Review::factory()->create(['score' => 3]);
    $review3 = Review::factory()->create(['score' => 1]);

    $results = Review::search('test')->keys();

    expect($results)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($results->count())->toBe(3)
        ->and($results->toArray())->sequence(
            fn ($id) => $id === $review2->getKey(),
            fn ($id) => $id === $review3->getKey(),
            fn ($id) => $id === $review1->getKey(),
        );
});

test('keys method handles empty search results', function () {
    // Create models but don't create any embeddings
    Review::factory()->count(3)->create();

    $results = Review::search('')->keys();

    expect($results)
        ->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($results->count())->toBe(0);
});

test('search method supports whereIn constraints', function () {
    // Create test models with different scores
    $review1 = Review::factory()->create(['score' => 5]);
    $review2 = Review::factory()->create(['score' => 3]);
    $review3 = Review::factory()->create(['score' => 1]);
    $review4 = Review::factory()->create(['score' => 5]);
    $review5 = Review::factory()->create(['score' => 2]);

    $results = Review::search('test')
        ->whereIn('score', [1, 5])
        ->get();

    expect($results)
        ->toHaveCount(3)
        ->and($results->pluck('score')->all())->toMatchArray([5, 1, 5]);
});

test('search method supports whereNotIn constraints', function () {
    // Create test models with different scores
    $review1 = Review::factory()->create(['score' => 5]);
    $review2 = Review::factory()->create(['score' => 3]);
    $review3 = Review::factory()->create(['score' => 1]);
    $review4 = Review::factory()->create(['score' => 5]);
    $review5 = Review::factory()->create(['score' => 2]);

    $results = Review::search('test')
        ->whereNotIn('score', [1, 5])
        ->get();

    expect($results)
        ->toHaveCount(2)
        ->and($results->pluck('score')->all())->toMatchArray([3, 2]);
});
