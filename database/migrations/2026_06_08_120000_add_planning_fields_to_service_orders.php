<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->timestamp('planned_start_at')->nullable()->after('kanban_position');
            $table->text('schedule_notes')->nullable()->after('hardware_received_notes');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['planned_start_at', 'schedule_notes']);
        });
    }
};
