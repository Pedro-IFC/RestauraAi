<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_customizations', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('tenant_id');
            $table->string('primary_color', 7)->default('#000000')->after('logo');
            $table->string('secondary_color', 7)->default('#FFFFFF')->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_customizations', function (Blueprint $table) {
            $table->dropColumn(['logo', 'primary_color', 'secondary_color']);
        });
    }
};
