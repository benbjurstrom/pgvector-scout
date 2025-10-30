<?php

use BenBjurstrom\PgvectorScout\Events\EmbeddingSaved;
use BenBjurstrom\PgvectorScout\Handlers\FakeHandler;
use BenBjurstrom\PgvectorScout\Tests\Support\Models\Review;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    // Load the reviews table migration for testing
    $migration = include __DIR__.'/Support/Migrations/2024_11_06_000000_create_reviews_table.php';
    $migration->up();

    // Load the embeddings table migration
    $migration = include __DIR__.'/Support/Migrations/2024_11_06_000000_create_embeddings_table.php';
    $migration->up();
});

test('EmbeddingSaved event is dispatched when embedding is created', function () {
    Event::fake([EmbeddingSaved::class]);

    // Create a test model which should trigger embedding creation
    $review = Review::factory()->create();

    // Assert the event was dispatched
    Event::assertDispatched(function (EmbeddingSaved $event) use ($review) {
        return $event->modelName === Review::class
            && $event->modelId === $review->id
            && $event->handler === FakeHandler::class
            && $event->wasRecentlyCreated === true;
    });
});

test('EmbeddingSaved event contains correct model information', function () {
    Event::fake([EmbeddingSaved::class]);

    $review = Review::factory()->create([
        'text' => 'This is a test review',
        'score' => 5,
    ]);

    Event::assertDispatched(function (EmbeddingSaved $event) use ($review) {
        // Verify the event has the correct model name
        expect($event->modelName)->toBe(Review::class);

        // Verify the event has the correct model ID
        expect($event->modelId)->toBe($review->id);

        // Verify the event has the handler class name
        expect($event->handler)->toBe(FakeHandler::class);

        // Verify the embedding was created (not updated)
        expect($event->wasRecentlyCreated)->toBe(true);

        return true;
    });
});

test('EmbeddingSaved event is dispatched when embedding is updated', function () {
    // Create a review first without event faking
    $review = Review::factory()->create(['text' => 'Original text']);

    // Now fake events for the update
    Event::fake([EmbeddingSaved::class]);

    // Update the review which should trigger embedding update
    $review->text = 'Updated text that is different';
    $review->save();

    // Assert the event was dispatched
    Event::assertDispatched(function (EmbeddingSaved $event) use ($review) {
        return $event->modelName === Review::class
            && $event->modelId === $review->id
            && $event->handler === FakeHandler::class
            && $event->wasRecentlyCreated === false;
    });
});
