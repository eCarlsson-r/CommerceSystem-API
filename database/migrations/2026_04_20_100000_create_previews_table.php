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
        Schema::create('previews', function (Blueprint $table) {
            $table->id();
            $table->string('preview_id', 100)->nullable()->index(); // Client-generated UUID
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_image', 2048)->nullable();
            $table->json('room_dimensions')->nullable(); // {width, height, depth}
            $table->string('selected_wall', 20)->nullable(); // front, back, left, right, all
            $table->decimal('tile_scale', 8, 2)->default(1.00);
            $table->integer('pattern_repeat')->default(53); // cm
            $table->json('wall_coverage')->nullable(); // Array of {wall, width, height, rolls_needed}
            $table->string('room_preview_url', 2048)->nullable(); // AI-generated image URL
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('previews');
    }
};
