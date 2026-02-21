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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->string('order_number')->unique(); // e.g., WEB-2026-001
            $table->enum('status', ['pending', 'paid', 'processing', 'shipped', 'cancelled']);
            $table->decimal('total_amount', 15, 2);
            $table->text('shipping_address')->nullable();
            $table->string('courier_service')->nullable(); // JNE, J&T, etc.
            $table->string('tracking_number')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained(); // Links to sales table once finalized
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
