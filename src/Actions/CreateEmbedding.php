<?php

namespace BenBjurstrom\PgvectorScout\Actions;

use BenBjurstrom\PgvectorScout\Models\Concerns\EmbeddableModel;
use BenBjurstrom\PgvectorScout\Models\Embedding;
use Ramsey\Uuid\Uuid;

class CreateEmbedding
{
    /**
     * Create or update an embedding for a given model
     *
     * @param EmbeddableModel $model
     * @return Embedding|null
     */
    public static function handle(EmbeddableModel $model): ?Embedding
    {
        // Get the searchable data
        $searchableData = $model->toSearchableArray();
        if (empty($searchableData)) {
            return null;
        }

        // Merge with scout metadata
        $data = array_merge(
            $searchableData,
            $model->scoutMetadata(),
        );

        $data = static::arrayToLabeledText($data);

        // Calculate hash of the content to determine if we need to update
        $contentHash = static::generateContentHash($data);
        $embeddingModel = config('pgvector-scout.model');

        // Check if we already have a vector for this model with the same hash
        $existingVector = static::findExistingEmbedding($model, $contentHash, $embeddingModel);
        if ($existingVector) {
            return $existingVector;
        }

        // Generate vector using configured embedding action
        $vector = static::generateVector($data, $embeddingModel);

        // Create or update the embedding
        return static::updateOrCreateEmbedding($model, $embeddingModel, $contentHash, $vector);
    }

    /**
     * Convert array data to labeled text format
     *
     * @param array $data
     * @return string
     */
    protected static function arrayToLabeledText(array $data): string
    {
        if (array_is_list($data)) {
            return $data[0];
        }

        return collect($data)
            ->map(function ($value, $key) {
                // Use Laravel's data_get() for nested arrays
                if (is_array($value)) {
                    $value = data_get($value, '*');
                }

                // Use Laravel's Str::of() for string manipulation
                return \Illuminate\Support\Str::of($key)
                    ->append(': ')
                    ->append(match (true) {
                        is_array($value) => json_encode($value),
                        is_bool($value) => $value ? 'true' : 'false',
                        is_null($value) => 'null',
                        default => $value
                    });
            })
            ->join(PHP_EOL);
    }

    /**
     * Generate a content hash
     *
     * @param string $data
     * @return string
     */
    protected static function generateContentHash(string $data): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_OID, $data)->toString();
    }

    /**
     * Find existing embedding with matching hash
     *
     * @param EmbeddableModel $model
     * @param string $contentHash
     * @param string $embeddingModel
     * @return Embedding|null
     */
    protected static function findExistingEmbedding(
        EmbeddableModel $model,
        string $contentHash,
        string $embeddingModel
    ): ?Embedding {
        return Embedding::query()
            ->where('embeddable_type', get_class($model))
            ->where('embeddable_id', $model->getKey())
            ->where('content_hash', $contentHash)
            ->where('embedding_model', $embeddingModel)
            ->first();
    }

    /**
     * Generate vector using configured embedding action
     *
     * @param string $data
     * @param string $embeddingModel
     * @return \Pgvector\Laravel\Vector
     */
    protected static function generateVector(string $data, string $embeddingModel): \Pgvector\Laravel\Vector
    {
        $embeddingAction = config('pgvector-scout.action');
        return $embeddingAction::handle($data, $embeddingModel);
    }

    /**
     * Create or update embedding record
     *
     * @param EmbeddableModel $model
     * @param string $embeddingModel
     * @param string $contentHash
     * @param \Pgvector\Laravel\Vector $vector
     * @return Embedding
     */
    protected static function updateOrCreateEmbedding(
        EmbeddableModel $model,
        string $embeddingModel,
        string $contentHash,
        \Pgvector\Laravel\Vector $vector
    ): Embedding {
        return Embedding::updateOrCreate(
            [
                'embeddable_type' => get_class($model),
                'embeddable_id' => $model->getKey(),
            ],
            [
                'embedding_model' => $embeddingModel,
                'content_hash' => $contentHash,
                'embedding' => $vector
            ]
        );
    }
}
