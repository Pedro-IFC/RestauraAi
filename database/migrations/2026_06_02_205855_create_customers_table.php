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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('cpf')->nullable(); // Utilizado para consultar status da OS [cite: 8]
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            
            $table->unique(['tenant_id', 'cpf']); // Um CPF é único dentro de uma mesma assistência
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
