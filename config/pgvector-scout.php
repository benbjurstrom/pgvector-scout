<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Handler Configuration
    |--------------------------------------------------------------------------
    |
    | This option controls which handler configuration to use by default.
    | You can change this to any of the handlers defined below.
    |
    */
    'default' => env('EMBEDDING_HANDLER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Handler Configurations
    |--------------------------------------------------------------------------
    |
    | Here you can define the configuration for different embedding handlers.
    | Each handler can have its own specific configuration options.
    |
    */
    'handlers' => [
        'openai' => [
            'class' => \BenBjurstrom\PgvectorScout\Handlers\OpenAiHandler::class,
            'model' => 'text-embedding-3-small',
            'dimensions' => 1536,
            'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'table' => 'embeddings',
        ],
    ],
];
