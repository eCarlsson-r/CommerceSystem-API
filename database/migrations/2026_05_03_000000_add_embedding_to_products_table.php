<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable pgvector extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        
        // Add embedding column for Vertex AI multimodal/text embeddings
        // Vertex AI multimodal embeddings (multimodalembedding@001) returns 1408 dimensions
        // text-embedding-004 returns 768
        // Using 1408 for maximum compatibility if using multimodal
        DB::statement('ALTER TABLE products ADD COLUMN embedding vector(1408)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE products DROP COLUMN IF EXISTS embedding');
    }
};
