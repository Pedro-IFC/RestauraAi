<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Plan;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cria um plano de teste para o sistema
        $plan = Plan::firstOrCreate(
            ['name' => 'Plano Ouro Teste'],
            [
                'price_monthly' => 97.00,
                'price_yearly' => 970.00,
                'features' => [
                    'kanban' => true,
                    'catalog' => true,
                    'dashboard' => true,
                    'customization' => true
                ],
                'trial_days_allowed' => 14,
            ]
        );

        // 2. Cria uma Assistência (Tenant) de teste
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'loja-teste'],
            [
                'plan_id' => $plan->id,
                'name' => 'Loja Teste',
                'document' => '00.000.000/0001-00',
                'status' => 'active',
            ]
        );

        // 3. Cria o usuário Administrador (Lojista)
        User::firstOrCreate(
            ['email' => 'admin@teste.com'],
            [
                'name' => 'Administrador da Loja',
                'password' => Hash::make('123456'), // Senha padrão criptografada
                'role' => 'admin',
                'tenant_id' => $tenant->id,
            ]
        );

        // 4. (Opcional) Cria o Administrador Master (Dono do SaaS FixGo/RestauraAí)
        User::firstOrCreate(
            ['email' => 'master@restauraai.com.br'],
            [
                'name' => 'Super Admin SaaS',
                'password' => Hash::make('123456'),
                'role' => 'superadmin',
                'tenant_id' => null, // O dono do SaaS não pertence a uma assistência específica
            ]
        );
    }
}
