<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Embedding Generation Configuration
    |--------------------------------------------------------------------------
    |
    | These options control how embeddings are generated. The 'handler' defines
    | a class that implements the EmbeddingHandler interface. The 'model' is
    | passed to the handler to determine which model to use.
    |
    */
    'embedding' => [
        'handler' => \BenBjurstrom\PgvectorScout\OpenAiHandler::class,
        'model' => 'text-embedding-3-small',
    ],
];
