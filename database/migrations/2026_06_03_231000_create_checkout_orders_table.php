<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('products');
            $table->string('status')->default('open');
            $table->string('payment_method')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('fixgo_commission_rate', 5, 2)->default(10);
            $table->decimal('fixgo_commission_amount', 10, 2)->default(0);
            $table->decimal('tenant_amount', 10, 2)->default(0);
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_orders');
    }
};
