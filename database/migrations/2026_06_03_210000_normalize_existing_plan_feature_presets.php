<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('plans')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan) {
                $name = str($plan->name)->ascii()->lower()->toString();
                $preset = match (true) {
                    str_contains($name, 'bronze') => 'bronze',
                    str_contains($name, 'prata') => 'prata',
                    str_contains($name, 'ouro'),
                    str_contains($name, 'profissional') => 'ouro',
                    default => null,
                };

                if (! $preset) {
                    return;
                }

                DB::table('plans')
                    ->where('id', $plan->id)
                    ->update(['features' => json_encode(Plan::presetFeatures($preset))]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
