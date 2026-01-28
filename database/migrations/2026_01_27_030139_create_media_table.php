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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');      // e.g., "iphone_15_pro.jpg"
            $table->string('mime_type');      // e.g., "image/jpeg" (replaces file-type)
            $table->string('extension', 10);  // e.g., "jpg"
            $table->unsignedBigInteger('size'); // In bytes
            $table->string('disk')->default('public'); // Local, S3, etc.
            $table->string('path');           // The actual folder path
            
            // The "Magic" part: Polymorphism
            $table->nullableMorphs('model');  // Adds model_id and model_type
            
            $table->timestamps(); // Replaces file-upload-date
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
