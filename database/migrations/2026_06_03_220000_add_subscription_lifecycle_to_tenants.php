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
        Schema::table('tenants', function (Blueprint $table) {
            $table->timestamp('payment_overdue_since')->nullable()->after('trial_ends_at');
            $table->unsignedSmallInteger('payment_grace_days')->default(7)->after('payment_overdue_since');
            $table->timestamp('suspended_at')->nullable()->after('payment_grace_days');
            $table->timestamp('canceled_at')->nullable()->after('suspended_at');
            $table->text('subscription_notes')->nullable()->after('canceled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'payment_overdue_since',
                'payment_grace_days',
                'suspended_at',
                'canceled_at',
                'subscription_notes',
            ]);
        });
    }
};
