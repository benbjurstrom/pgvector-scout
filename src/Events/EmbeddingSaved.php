<?php

namespace BenBjurstrom\PgvectorScout\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmbeddingSaved
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  string  $modelName  The fully qualified class name of the searchable model
     * @param  int|string  $modelId  The ID of the searchable model
     * @param  string  $handler  The fully qualified class name of the handler used
     * @param  bool  $wasRecentlyCreated  Whether the embedding was created (true) or updated (false)
     */
    public function __construct(
        public readonly string $modelName,
        public readonly int|string $modelId,
        public readonly string $handler,
        public readonly bool $wasRecentlyCreated
    ) {}
}
