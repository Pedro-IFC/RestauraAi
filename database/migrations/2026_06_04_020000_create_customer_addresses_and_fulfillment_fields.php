<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->default('Principal');
            $table->string('recipient_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('street');
            $table->string('number', 30);
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city');
            $table->string('state', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'tenant_id']);
        });

        Schema::table('checkout_orders', function (Blueprint $table) {
            $table->foreignId('customer_address_id')->nullable()->after('customer_id')->constrained('customer_addresses')->nullOnDelete();
            $table->string('fulfillment_method')->default('pickup')->after('payment_method');
            $table->string('fulfillment_status')->default('pending')->after('fulfillment_method');
        });
    }

    public function down(): void
    {
        Schema::table('checkout_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_address_id']);
            $table->dropColumn(['customer_address_id', 'fulfillment_method', 'fulfillment_status']);
        });

        Schema::dropIfExists('customer_addresses');
    }
};
