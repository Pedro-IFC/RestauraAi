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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['product', 'supply']); // Produto de Venda ou Insumo Técnico [cite: 27]
            $table->boolean('is_for_sale')->default(false); // Flag para exibir no catálogo público [cite: 25]
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('min_stock_alert', 10, 2)->default(5); // Para o Dashboard (RF-10) 
            $table->softDeletes();
            $table->timestamps();
            
            // Índice composto para buscas rápidas no catálogo
            $table->index(['tenant_id', 'is_for_sale']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
