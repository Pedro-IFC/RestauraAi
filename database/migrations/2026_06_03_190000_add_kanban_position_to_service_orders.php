<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->unsignedInteger('kanban_position')->default(0)->after('status');
        });

        DB::table('service_orders')
            ->orderBy('kanban_column_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy('kanban_column_id')
            ->each(function ($orders) {
                $orders->values()->each(function ($order, int $index) {
                    DB::table('service_orders')
                        ->where('id', $order->id)
                        ->update(['kanban_position' => $index + 1]);
                });
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('kanban_position');
        });
    }
};
