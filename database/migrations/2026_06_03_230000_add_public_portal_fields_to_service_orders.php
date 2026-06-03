<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('defect_symptoms');
            $table->timestamp('budget_decided_at')->nullable()->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'budget_decided_at']);
        });
    }
};
