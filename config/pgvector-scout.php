<?php

use BenBjurstrom\PgvectorScout\Handlers;

return [
    /*
    |--------------------------------------------------------------------------
    | Embedding Index Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can define the configuration for different embedding indexes.
    | Each index can have its own specific configuration options.
    |
    */
    'indexes' => [
        'openai' => [
            'handler' => Handlers\OpenAiHandler::class,
            'model' => 'text-embedding-3-small',
            'dimensions' => 256, // See Reducing embedding dimensions https://platform.openai.com/docs/guides/embeddings#use-cases
            'url' => 'https://api.openai.com/v1',
            'api_key' => env('OPENAI_API_KEY'),
            'table' => 'openai_embeddings',
        ],
        'gemini' => [
            'handler' => Handlers\GeminiHandler::class,
            'model' => 'text-embedding-004',
            'dimensions' => 256,
            'url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key' => env('GEMINI_API_KEY'),
            'table' => 'gemini_embeddings',
            'task' => 'SEMANTIC_SIMILARITY', // https://ai.google.dev/api/embeddings#tasktype
        ],
        'ollama' => [
            'handler' => Handlers\OllamaHandler::class,
            'model' => 'nomic-embed-text',
            'dimensions' => 768,
            'url' => 'http://localhost:11434/api/embeddings',
            'api_key' => 'none',
            'table' => 'ollama_embeddings',
        ],
        'fake' => [ // Used for testing
            'handler' => Handlers\FakeHandler::class,
            'model' => 'fake',
            'dimensions' => 3,
            'url' => 'https://example.com',
            'api_key' => '123',
            'table' => 'fake_embeddings',
        ],
    ],
];
