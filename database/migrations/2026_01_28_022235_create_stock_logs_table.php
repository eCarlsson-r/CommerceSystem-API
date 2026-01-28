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
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->string('reference_id', 20); // Invoice #, Transfer ID, or Adjustment ID
            $table->enum('type', ['sale', 'purchase', 'transfer', 'adjustment', 'return']);
            $table->string('description', 250);
            $table->integer('quantity_change'); // Positive for 'ADD', Negative for 'GET'
            $table->integer('balance_after');   // The "Kartu Stok" running total
            $table->foreignId('user_id')->constrained(); // Who performed the action
            $table->timestamps(); // Replaces stock-log-date and stock-log-time
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};
