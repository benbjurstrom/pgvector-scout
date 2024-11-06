<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Pgvector\Laravel\Vector;
use RuntimeException;
use Illuminate\Support\Facades\Cache;

class GetOpenAiEmbeddings
{
    /**
     * Get OpenAI embeddings for a given input
     *
     * @param string $input
     * @param string $embeddingModel
     * @return Vector
     * @throws RuntimeException
     */
    public static function handle(string $input, string $embeddingModel): Vector
    {

        $cacheKey = 'openai_embedding:' . sha1($input . $embeddingModel);

        $embedding = Cache::rememberForever($cacheKey, function () use ($input, $embeddingModel) {
            $apiKey = static::getApiKey();

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'input' => $input,
                'model' => $embeddingModel,
            ]);

            static::validateResponse($response);
            return static::extractEmbedding($response);
        });

        return new Vector($embedding);
    }

    /**
     * Validate and return the OpenAI API key
     *
     * @throws RuntimeException
     */
    protected static function getApiKey(): string
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('OpenAI API key not found. Please set your OpenAI API key in the `services.openai.api_key` config.');
        }

        return $apiKey;
    }

    /**
     * Validate the API response
     *
     * @throws RuntimeException
     */
    protected static function validateResponse(Response $response): void
    {
        if (!$response->successful()) {
            throw new RuntimeException(
                'OpenAI API request failed: ' . ($response['error']['message'] ?? $response->body())
            );
        }
    }

    /**
     * Extract the embedding from the response
     *
     * @throws RuntimeException
     */
    protected static function extractEmbedding(Response $response): array
    {
        $embedding = $response->json('data.0.embedding');

        if (empty($embedding)) {
            throw new RuntimeException(
                'No embedding found in OpenAI response: ' . $response->body()
            );
        }

        return $embedding;
    }
}
