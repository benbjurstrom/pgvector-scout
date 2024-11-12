<?php

namespace BenBjurstrom\PgvectorScout\Config;

use RuntimeException;

class HandlerConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        public readonly string $model,
        public readonly int $dimensions,
        public readonly string $table,
        public readonly string $url,
        public readonly string $apiKey,
    ) {
        $this->validate();
    }

    /**
     * Create a new instance from the default handler configuration
     */
    public static function fromConfig(): self
    {
        $default = config('pgvector-scout.default');
        if (empty($default)) {
            throw new RuntimeException('No default handler configured in pgvector-scout config.');
        }

        $config = config("pgvector-scout.handlers.{$default}");
        if (empty($config)) {
            throw new RuntimeException("No configuration found for handler '{$default}'.");
        }

        return new self(
            name: $default,
            class: $config['class'] ?? throw new RuntimeException("No class configured for handler '{$default}'."),
            model: $config['model'] ?? throw new RuntimeException("No model configured for handler '{$default}'."),
            dimensions: $config['dimensions'] ?? throw new RuntimeException("No dimensions configured for handler '{$default}'."),
            table: $config['table'] ?? throw new RuntimeException("No table configured for handler '{$default}'."),
            url: $config['url'] ?? throw new RuntimeException("No URL configured for handler '{$default}'."),
            apiKey: $config['api_key'] ?? throw new RuntimeException("No API key configured for handler '{$default}'."),
        );
    }

    /**
     * Validate the configuration values
     *
     * @throws RuntimeException
     */
    protected function validate(): void
    {
        if (! class_exists($this->class)) {
            throw new RuntimeException("Handler class '{$this->class}' does not exist.");
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