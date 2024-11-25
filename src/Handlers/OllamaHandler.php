<?php

namespace BenBjurstrom\PgvectorScout\Handlers;

use BenBjurstrom\PgvectorScout\HandlerContract;
use BenBjurstrom\PgvectorScout\IndexConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Pgvector\Laravel\Vector;
use RuntimeException;

class OllamaHandler implements HandlerContract
{
    public static function handle(string $input, IndexConfig $config): Vector
    {
        $cacheKey = $config->name.':'.$config->model.':'.sha1($input);

        $embedding = Cache::rememberForever($cacheKey, function () use ($input, $config) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($config->url, [
                'model' => $config->model,
                'prompt' => $input,
            ]);

            static::validateResponse($response);

            return static::extractEmbedding($response);
        });

        return new Vector($embedding);
    }

    protected static function validateResponse(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException(
                'Ollama API request failed: '.($response['error']['message'] ?? $response->body())
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
        $embedding = $response->json('embedding');

        if (empty($embedding)) {
            throw new RuntimeException(
                'No embedding found in Ollama response: '.$response->body()
            );
        }

        return $embedding;
    }
}
