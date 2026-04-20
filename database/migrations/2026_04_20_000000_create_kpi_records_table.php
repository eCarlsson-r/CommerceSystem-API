<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kpi_records', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // 'commercial', 'operational', 'governance'
            $table->string('metric');
            $table->decimal('value', 15, 4);
            $table->json('context')->nullable();
            $table->string('source')->default('api'); // 'api', 'web', 'pos'
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            // Indexes for efficient querying
            $table->index('category');
            $table->index('metric');
            $table->index('source');
            $table->index(['category', 'metric', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_records');
    }
};
