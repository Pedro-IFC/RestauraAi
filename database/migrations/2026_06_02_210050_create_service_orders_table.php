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
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('kanban_column_id')->constrained();
            $table->string('device_model'); // [cite: 7]
            $table->text('defect_symptoms'); // [cite: 7]
            $table->enum('status', ['pending', 'budgeting', 'approved', 'rejected', 'finished'])->default('pending');
            $table->decimal('total_cost', 10, 2)->default(0); // Custo dos insumos usados 
            $table->decimal('total_price', 10, 2)->default(0); // Preço cobrado do cliente 
            $table->timestamp('deadline_at')->nullable(); // Cronograma de prazos [cite: 30]
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
