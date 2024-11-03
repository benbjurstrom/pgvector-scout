<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use Pgvector\Laravel\Vector;

class GetOpenAiEmbeddings
{

    /**
     * Get OpenAI embeddings for a given input
     *
     * @param string $input
     * @param string $embeddingModel
     * @return Vector
     */
    public static function handle(string $input, string $embeddingModel): Vector
    {
        $apiKey = config('services.openai.api_key');
        // validate key exists

        // HTTP request to OpenAI API using a key from the services file and the laravel HTTP client

        // Extract the embeddings from the response and return them as a Vector

        return new Vector(array_fill(0, 1536, 0.0));
    }
}
