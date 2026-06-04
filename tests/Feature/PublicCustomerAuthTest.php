<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\KanbanColumn;
use App\Models\MagicLoginCode;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\MagicLoginCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicCustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_from_tenant_public_page_and_get_tenant_context(): void
    {
        $tenant = $this->tenant('fix-cell');

        $this->post('/fix-cell/cadastro', [
            'name' => 'Cliente Unificado',
            'email' => 'cliente@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'cpf' => '123.456.789-00',
            'phone' => '(47) 99999-0000',
        ])
            ->assertRedirect(route('public.account.index', $tenant->slug))
            ->assertSessionHas('public_tenant_slug', 'fix-cell');

        $user = User::where('email', 'cliente@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('customer', $user->role);
        $this->assertNull($user->tenant_id);
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'cpf' => '123.456.789-00',
        ]);
    }

    public function test_same_customer_login_creates_context_per_tenant_without_mixing_history(): void
    {
        $tenant = $this->tenant('loja-a');
        $otherTenant = $this->tenant('loja-b');
        $user = User::create([
            'name' => 'Cliente Global',
            'email' => 'global@example.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'tenant_id' => null,
        ]);

        $customerA = Customer::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'Cliente Global',
            'email' => $user->email,
        ]);
        $customerB = Customer::create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $user->id,
            'name' => 'Cliente Global',
            'email' => $user->email,
        ]);

        $orderA = $this->serviceOrder($tenant, $customerA, 'Notebook Loja A');
        $this->serviceOrder($otherTenant, $customerB, 'Celular Loja B');

        $this->post('/loja-a/login', [
            'email' => 'global@example.com',
            'password' => 'password123',
        ])
            ->assertRedirect(route('public.account.index', $tenant->slug))
            ->assertSessionHas('public_tenant_slug', 'loja-a');

        $this->get('/loja-a/acompanhamento')
            ->assertOk()
            ->assertSee('Chamado #'.$orderA->id)
            ->assertSee('Notebook Loja A')
            ->assertDontSee('Celular Loja B');

        $this->post('/loja-b/login', [
            'email' => 'global@example.com',
            'password' => 'password123',
        ])->assertSessionHas('public_tenant_slug', 'loja-b');

        $this->get('/loja-b/acompanhamento')
            ->assertOk()
            ->assertSee('Celular Loja B')
            ->assertDontSee('Notebook Loja A');
    }

    public function test_authenticated_customer_service_order_is_linked_to_global_user_for_that_tenant(): void
    {
        $tenant = $this->tenant('assistencia-auth');
        $user = User::create([
            'name' => 'Cliente OS',
            'email' => 'os@example.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'tenant_id' => null,
        ]);

        $this->actingAs($user)
            ->post('/assistencia-auth/chamados', [
                'name' => 'Cliente OS',
                'cpf' => '222.333.444-55',
                'phone' => '(47) 98888-0000',
                'email' => 'os@example.com',
                'device_model' => 'iPhone 15',
                'defect_symptoms' => 'Bateria descarregando rápido.',
            ])
            ->assertRedirect('/assistencia-auth');

        $customer = Customer::where('tenant_id', $tenant->id)->firstOrFail();
        $serviceOrder = ServiceOrder::firstOrFail();

        $this->assertSame($user->id, $customer->user_id);
        $this->assertSame($customer->id, $serviceOrder->customer_id);
    }

    public function test_customer_can_request_magic_login_code_by_email(): void
    {
        Notification::fake();

        $tenant = $this->tenant('magic-request');

        $this->post('/magic-request/login/rapido', [
            'email' => 'rapido@example.com',
            'intended' => '/magic-request/checkout',
        ])
            ->assertRedirect(route('public.customer.magic.verify', $tenant->slug))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('magic_login_codes', [
            'tenant_id' => $tenant->id,
            'email' => 'rapido@example.com',
            'intended_path' => '/magic-request/checkout',
            'consumed_at' => null,
        ]);

        Notification::assertSentOnDemand(MagicLoginCodeNotification::class);
    }

    public function test_magic_code_logs_existing_customer_into_requested_tenant_context(): void
    {
        Notification::fake();

        $tenant = $this->tenant('magic-existing');
        $user = User::create([
            'name' => 'Cliente Codigo',
            'email' => 'codigo@example.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'tenant_id' => null,
        ]);

        $this->post('/magic-existing/login/rapido', [
            'email' => 'codigo@example.com',
            'intended' => '/magic-existing/checkout',
        ]);

        $magicCode = MagicLoginCode::firstOrFail();
        $magicCode->update(['code_hash' => Hash::make('123456')]);

        $this->post('/magic-existing/login/codigo', [
            'email' => 'codigo@example.com',
            'code' => '123456',
        ])
            ->assertRedirect('/magic-existing/checkout')
            ->assertSessionHas('public_tenant_slug', 'magic-existing');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($magicCode->fresh()->consumed_at);
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'email' => 'codigo@example.com',
        ]);
    }

    public function test_magic_code_creates_customer_account_when_email_is_new(): void
    {
        Notification::fake();

        $tenant = $this->tenant('magic-new');

        $this->post('/magic-new/login/rapido', [
            'email' => 'novo.cliente@example.com',
        ]);

        MagicLoginCode::firstOrFail()->update(['code_hash' => Hash::make('654321')]);

        $this->post('/magic-new/login/codigo', [
            'email' => 'novo.cliente@example.com',
            'code' => '654321',
        ])->assertRedirect(route('public.account.index', $tenant->slug));

        $user = User::where('email', 'novo.cliente@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('customer', $user->role);
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_magic_code_rejects_internal_user_accounts(): void
    {
        Notification::fake();

        $tenant = $this->tenant('magic-interno');
        User::create([
            'name' => 'Tecnico Interno',
            'email' => 'interno@example.com',
            'password' => Hash::make('password123'),
            'role' => 'technician',
            'tenant_id' => $tenant->id,
        ]);

        $this->post('/magic-interno/login/rapido', [
            'email' => 'interno@example.com',
        ]);

        MagicLoginCode::firstOrFail()->update(['code_hash' => Hash::make('111222')]);

        $this->post('/magic-interno/login/codigo', [
            'email' => 'interno@example.com',
            'code' => '111222',
        ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function tenant(string $slug): Tenant
    {
        $plan = Plan::create([
            'name' => 'Plano '.$slug,
            'price_monthly' => 99,
            'features' => Plan::presetFeatures('ouro'),
            'trial_days_allowed' => 7,
        ]);

        return Tenant::create([
            'plan_id' => $plan->id,
            'name' => 'Assistencia '.$slug,
            'slug' => $slug,
            'document' => substr(md5($slug), 0, 14),
            'status' => 'active',
        ]);
    }

    private function serviceOrder(Tenant $tenant, Customer $customer, string $deviceModel): ServiceOrder
    {
        $column = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Triagem',
            'order_index' => 1,
        ]);

        return ServiceOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'device_model' => $deviceModel,
            'defect_symptoms' => 'Sintoma teste.',
            'status' => 'pending',
            'total_cost' => 0,
            'total_price' => 0,
        ]);
    }
}
