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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ex: Bronze, Prata, Ouro [cite: 36]
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->json('features'); // Feature Gating: define limites técnicos (ex: max_os_per_month) 
            $table->integer('trial_days_allowed')->default(0); // [cite: 45]
            $table->softDeletes(); // Preserva histórico de planos inativados
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
