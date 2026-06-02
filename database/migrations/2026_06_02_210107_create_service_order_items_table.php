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
        Schema::create('service_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();
            $table->decimal('quantity', 8, 2); // Pode ser fracionado (ex: 1.5g de pasta térmica) [cite: 28]
            $table->decimal('unit_cost', 10, 2); // Snapshot do custo no momento da OS
            $table->decimal('unit_price', 10, 2); // Snapshot do preço de venda no momento da OS
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_order_items');
    }
};
