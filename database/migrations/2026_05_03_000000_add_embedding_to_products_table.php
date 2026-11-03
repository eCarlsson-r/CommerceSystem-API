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
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: Enable pgvector extension & add vector column
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            DB::statement('ALTER TABLE products ADD COLUMN embedding vector(768)');
        } elseif ($driver === 'mariadb' || $driver === 'mysql') {
            // MariaDB 11.8+ Native Vector
            DB::statement('ALTER TABLE products ADD COLUMN embedding VECTOR(768)');
            
            // Optional: You can also add an HNSW index directly for high-performance cosine search
            DB::statement('ALTER TABLE products ADD VECTOR INDEX vec_idx (embedding) DISTANCE=COSINE');
        } else {
            // Fallback for older database versions or SQLite: store as JSON
            Schema::table('products', function (Blueprint $table) {
                $table->json('embedding')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        // Both PostgreSQL and MariaDB 11.8 support DROP COLUMN IF EXISTS
        DB::statement('ALTER TABLE products DROP COLUMN IF EXISTS embedding');
    }
};
