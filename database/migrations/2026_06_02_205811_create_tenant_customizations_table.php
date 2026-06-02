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
        Schema::create('tenant_customizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->json('banners')->nullable(); // Arrays de URLs de imagens [cite: 14]
            $table->text('about_text')->nullable(); // Descrição e políticas [cite: 15]
            $table->string('instagram_handle')->nullable(); // [cite: 16]
            $table->text('address_text')->nullable(); // [cite: 17]
            $table->text('google_maps_iframe')->nullable(); // [cite: 18, 33]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_customizations');
    }
};
