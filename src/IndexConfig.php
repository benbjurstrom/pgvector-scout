<?php

namespace BenBjurstrom\PgvectorScout;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class IndexConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $handler,
        public readonly string $model,
        public readonly int $dimensions,
        public readonly string $table,
        public readonly string $url,
        public readonly string $apiKey,
        public readonly ?string $task,
    ) {
        $this->validate();
    }

    public static function fromModel(Model $model): self
    {
        if (! method_exists($model, 'searchableAs')) {
            throw new \RuntimeException('Model '.get_class($model).' does not implement the Searchable trait.');
        }

        return self::from($model->searchableAs());
    }

    /**
     * Create a new instance from the given index configuration
     */
    public static function from(string $index): self
    {
        $config = config("pgvector-scout.indexes.{$index}");
        if (empty($config)) {
            throw new RuntimeException("No configuration found for index '{$index}'.");
        }

        return new self(
            name: $index,
            handler: $config['handler'] ?? throw new RuntimeException("No handler configured for index '{$index}'."),
            model: $config['model'] ?? throw new RuntimeException("No model configured for index '{$index}'."),
            dimensions: $config['dimensions'] ?? throw new RuntimeException("No dimensions configured for index '{$index}'."),
            table: $config['table'] ?? throw new RuntimeException("No table configured for index '{$index}'."),
            url: $config['url'] ?? throw new RuntimeException("No URL configured for index '{$index}'."),
            apiKey: $config['api_key'] ?? throw new RuntimeException("No API key configured for index '{$index}'."),
            task: $config['task'] ?? null,
        );
    }

    /**
     * Validate the configuration values
     *
     * @throws RuntimeException
     */
    protected function validate(): void
    {
        if (! class_exists($this->handler)) {
            throw new RuntimeException("Handler class '{$this->handler}' does not exist.");
        }

        if (! is_subclass_of($this->handler, HandlerContract::class)) {
            throw new RuntimeException(
                "Embedding handler class '{$this->handler}' must implement EmbeddingHandler interface."
            );
        }

        if ($this->dimensions < 1) {
            throw new RuntimeException('Dimensions must be greater than 0.');
        }

        if (! filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("Invalid URL: {$this->url}");
        }

        if (strlen($this->table) < 1) {
            throw new RuntimeException('Table name cannot be empty.');
        }

        if (strlen($this->apiKey) < 1) {
            throw new RuntimeException('API key cannot be empty.');
        }
    }
}
