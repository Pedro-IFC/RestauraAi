<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\KanbanColumn;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KanbanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_kanban_creates_default_columns_for_assistance(): void
    {
        [$tenant, $user] = $this->tenantAndUser();

        $this->actingAs($user)
            ->get(route('tenant.kanban.index'))
            ->assertOk()
            ->assertSee('Triagem')
            ->assertSee('Orçamento')
            ->assertSee('Aguardando Peça')
            ->assertSee('Na Bancada')
            ->assertSee('Testes')
            ->assertSee('Pronto');

        $this->assertDatabaseCount('kanban_columns', 6);
        $this->assertDatabaseHas('kanban_columns', [
            'tenant_id' => $tenant->id,
            'name' => 'Triagem',
            'order_index' => 1,
        ]);
    }

    public function test_public_service_order_enters_first_kanban_column(): void
    {
        [$tenant] = $this->tenantAndUser();

        $this->post(route('public.os.store', $tenant->slug), [
            'name' => 'Cliente Publico',
            'cpf' => '12345678900',
            'phone' => '47999999999',
            'email' => 'cliente@example.com',
            'device_model' => 'iPhone 13',
            'defect_symptoms' => 'Tela quebrada',
        ])
            ->assertRedirect('/'.$tenant->slug);

        $triage = KanbanColumn::where('tenant_id', $tenant->id)->where('name', 'Triagem')->firstOrFail();

        $this->assertDatabaseHas('service_orders', [
            'tenant_id' => $tenant->id,
            'kanban_column_id' => $triage->id,
            'kanban_position' => 1,
            'device_model' => 'iPhone 13',
            'status' => 'pending',
        ]);
    }

    public function test_can_move_service_order_between_kanban_columns(): void
    {
        [$tenant, $user] = $this->tenantAndUser();
        $triage = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Triagem',
            'order_index' => 1,
        ]);
        $budget = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Orçamento',
            'order_index' => 2,
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente',
            'cpf' => '987',
        ]);
        $serviceOrder = ServiceOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $triage->id,
            'kanban_position' => 1,
            'device_model' => 'Notebook Dell',
            'defect_symptoms' => 'Não liga',
            'status' => 'pending',
            'total_cost' => 0,
            'total_price' => 0,
        ]);

        $this->actingAs($user)
            ->patch(route('tenant.kanban.move'), [
                'service_order_id' => $serviceOrder->id,
                'kanban_column_id' => $budget->id,
                'position' => 1,
            ])
            ->assertRedirect(route('tenant.kanban.index'));

        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'kanban_column_id' => $budget->id,
            'kanban_position' => 1,
            'status' => 'budgeting',
        ]);
    }

    public function test_service_order_created_with_received_hardware_advances_to_next_stage(): void
    {
        [$tenant, $user] = $this->tenantAndUser();
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente',
            'cpf' => '654',
        ]);
        $triage = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Triagem',
            'order_index' => 1,
        ]);
        $budget = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Orçamento',
            'order_index' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('ordens-servico.store'), [
                'customer_id' => $customer->id,
                'kanban_column_id' => $triage->id,
                'device_model' => 'MacBook Pro',
                'defect_symptoms' => 'Sem video',
                'hardware_received_at' => now()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $serviceOrder = ServiceOrder::firstWhere('device_model', 'MacBook Pro');

        $this->assertSame($budget->id, $serviceOrder->kanban_column_id);
        $this->assertSame('budgeting', $serviceOrder->status);
        $this->assertNotNull($serviceOrder->hardware_received_at);
    }

    public function test_tenant_can_create_service_order_with_new_customer_from_panel(): void
    {
        [$tenant, $user] = $this->tenantAndUser();
        $triage = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Triagem',
            'order_index' => 1,
        ]);

        $this->actingAs($user)
            ->post(route('ordens-servico.store'), [
                'customer_name' => 'Cliente Balcao',
                'customer_cpf' => '11122233344',
                'customer_phone' => '47988887777',
                'customer_email' => 'balcao@example.com',
                'kanban_column_id' => $triage->id,
                'device_model' => 'Samsung A54',
                'defect_symptoms' => 'Conector de carga falhando',
            ])
            ->assertRedirect();

        $customer = Customer::where('tenant_id', $tenant->id)
            ->where('cpf', '11122233344')
            ->firstOrFail();

        $this->assertSame('Cliente Balcao', $customer->name);
        $this->assertDatabaseHas('service_orders', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $triage->id,
            'device_model' => 'Samsung A54',
            'status' => 'pending',
        ]);
    }

    public function test_tenant_create_form_initializes_default_columns(): void
    {
        [$tenant, $user] = $this->tenantAndUser();

        $this->actingAs($user)
            ->get(route('ordens-servico.create'))
            ->assertOk()
            ->assertSee('Novo chamado')
            ->assertSee('Triagem')
            ->assertSee('Nome do novo cliente');

        $this->assertDatabaseHas('kanban_columns', [
            'tenant_id' => $tenant->id,
            'name' => 'Triagem',
            'order_index' => 1,
        ]);
    }

    private function tenantAndUser(): array
    {
        $plan = Plan::create([
            'name' => 'Plano Teste',
            'price_monthly' => 99,
            'features' => ['kanban' => true],
            'trial_days_allowed' => 7,
        ]);

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'name' => 'Assistencia Teste',
            'slug' => 'assistencia-teste',
            'document' => '00.000.000/0001-00',
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tecnico',
            'email' => 'tecnico-kanban@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        return [$tenant, $user];
    }
}
