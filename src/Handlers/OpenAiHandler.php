<?php

namespace BenBjurstrom\PgvectorScout\Handlers;

use BenBjurstrom\PgvectorScout\HandlerConfig;
use BenBjurstrom\PgvectorScout\HandlerContract;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pgvector\Laravel\Vector;
use RuntimeException;

class OpenAiHandler implements HandlerContract
{
    /**
     * Get OpenAI embeddings for a given input
     *
     * @throws RuntimeException
     */
    public static function handle(string $input, HandlerConfig $config): Vector
    {
        $cacheKey = $config->name.':'.$config->model.':'.sha1($input);

        $embedding = Cache::rememberForever($cacheKey, function () use ($input, $config) {

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$config->apiKey,
                'Content-Type' => 'application/json',
            ])->post($config->url.'/embeddings', [
                'input' => $input,
                'model' => $config->model,
                'dimensions' => $config->dimensions,
            ]);

            static::validateResponse($response);

            return static::extractEmbedding($response);
        });

        return new Vector($embedding);
    }

    /**
     * Validate the API response
     *
     * @throws RuntimeException
     */
    protected static function validateResponse(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                'OpenAI API request failed: '.($response['error']['message'] ?? $response->body())
            );
        }
    }

    /**
     * Extract the embedding from the response
     *
     * @return array<int, float>
     *
     * @throws RuntimeException
     */
    protected static function extractEmbedding(Response $response): array
    {
        $embedding = $response->json('data.0.embedding');

        if (empty($embedding)) {
            throw new RuntimeException(
                'No embedding found in OpenAI response: '.$response->body()
            );
        }

        return $embedding;
    }
}
