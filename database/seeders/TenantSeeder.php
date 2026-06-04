<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantCustomization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Garante que existe um plano base para associar
        $plan = Plan::firstOrCreate(
            ['name' => 'Plano Profissional'],
            [
                'price_monthly' => 89.90,
                'features' => Plan::presetFeatures('ouro'),
                'trial_days_allowed' => 7,
            ]
        );

        // 2. Cria a Assistência (Tenant)
        $tenantName = 'PH Informática';

        $tenant = Tenant::firstOrCreate(
            ['slug' => Str::slug($tenantName)],
            [
                'plan_id' => $plan->id,
                'name' => $tenantName,
                'document' => '12.345.678/0001-99',
                'status' => 'active',
            ]
        );

        // 3. Define a Identidade Visual e Informações de Contacto
        TenantCustomization::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'primary_color' => '#facc15', // Amarelo principal
                'secondary_color' => '#fffbeb', // Fundo claro quente
                'about_text' => 'Especialistas em manutenção avançada de computadores, consolas de videojogos e otimização de infraestruturas de rede.',
                'address_text' => 'Rio do Sul, Santa Catarina, Brasil',
            ]
        );

        // 4. Cria o Utilizador Lojista / Administrador desta assistência
        User::firstOrCreate(
            ['email' => 'contato@phinformatica.com.br'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Pedro',
                'password' => Hash::make('senha123'),
                'role' => 'admin',
            ]
        );
    }
}
