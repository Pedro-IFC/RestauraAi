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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained();
            $table->string('name');
            $table->string('slug')->unique(); // Identificador dinâmico na URL (ex: /ph-informatica) [cite: 3]
            $table->string('document')->unique(); // CNPJ/CPF da assistência
            $table->enum('status', ['active', 'trial', 'suspended', 'canceled'])->default('trial'); // [cite: 43]
            $table->timestamp('trial_ends_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
