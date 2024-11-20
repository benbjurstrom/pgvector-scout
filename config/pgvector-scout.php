<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Embedding Handler Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can define the configuration for different embedding handlers.
    | Each handler can have its own specific configuration options.
    |
    */
    'indexes' => [
        'openai' => [
            'handler' => \BenBjurstrom\PgvectorScout\Handlers\OpenAiHandler::class,
            'model' => 'text-embedding-3-small',
            'dimensions' => 256, // See Reducing embedding dimensions https://platform.openai.com/docs/guides/embeddings#use-cases
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'table' => 'embeddings',
        ],
        'gemini' => [
            'handler' => \BenBjurstrom\PgvectorScout\Handlers\GeminiHandler::class,
            'model' => 'text-embedding-004',
            'dimensions' => 256,
            'url' => env('GEMINI_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'api_key' => env('GEMINI_API_KEY'),
            'table' => 'embeddings',
            'task' => 'SEMANTIC_SIMILARITY', // https://ai.google.dev/api/embeddings#tasktype
        ],
        'fake' => [
            'handler' => \BenBjurstrom\PgvectorScout\Handlers\FakeHandler::class,
            'model' => 'fake',
            'dimensions' => 3,
            'url' => 'https://example.com',
            'api_key' => '123',
            'table' => 'fake_embeddings',
        ],
    ],
];
