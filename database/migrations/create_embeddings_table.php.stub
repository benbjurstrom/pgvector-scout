<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use BenBjurstrom\PgvectorScout\HandlerConfig;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First ensure the pgvector extension is installed
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        $config = HandlerConfig::fromConfig();
        $table = $config->table;

        Schema::create($table, function (Blueprint $table) use ($config) {
            $table->id();
            $table->morphs('embeddable');
            $table->string('embedding_model');
            $table->uuid('content_hash');
            $table->vector('embedding', $config->dimensions);
            $table->timestamps();

            // Add indexes
            $table->index(['content_hash']);
            $table->unique(['embeddable_type', 'embeddable_id']);
        });

        // Create HNSW index on the vector column
        DB::statement("CREATE INDEX {$table}_vector_idx ON {$table} USING hnsw (embedding vector_cosine_ops)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $config = HandlerConfig::fromConfig();
        $table = $config->table;

        Schema::dropIfExists($table);
    }
};
