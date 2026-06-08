<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'tenant_id']);
        });

        Schema::create('checkout_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->unique(['checkout_cart_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_cart_items');
        Schema::dropIfExists('checkout_carts');
    }
};
