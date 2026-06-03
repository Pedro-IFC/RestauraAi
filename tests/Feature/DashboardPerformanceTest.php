<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\KanbanColumn;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_business_performance_metrics(): void
    {
        $this->travelTo(now()->setDate(2026, 6, 3)->setTime(12, 0));

        [$tenant, $user] = $this->tenantAndUser('dashboard-teste', 'dashboard@example.com');
        $column = $this->column($tenant);
        $customer = $this->customer($tenant);

        $finishedOrder = $this->serviceOrder($tenant, $customer, $column, [
            'status' => 'finished',
            'total_price' => 500,
            'total_cost' => 200,
            'updated_at' => now(),
        ]);

        TimeEntry::create([
            'service_order_id' => $finishedOrder->id,
            'user_id' => $user->id,
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'duration_seconds' => 3600,
        ]);

        $this->serviceOrder($tenant, $customer, $column, [
            'status' => 'approved',
            'updated_at' => now(),
        ]);

        $this->serviceOrder($tenant, $customer, $column, [
            'status' => 'rejected',
            'updated_at' => now(),
        ]);

        Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pasta térmica',
            'type' => 'supply',
            'is_for_sale' => false,
            'cost_price' => 10,
            'sale_price' => 20,
            'stock_quantity' => 1,
            'min_stock_alert' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('tenant.dashboard', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('R$ 300,00')
            ->assertSee('01:00')
            ->assertSee('66,7%')
            ->assertSee('Pasta térmica')
            ->assertSee('Seu item está abaixo do limite mínimo.');
    }

    public function test_dashboard_does_not_show_metrics_from_another_tenant(): void
    {
        $this->travelTo(now()->setDate(2026, 6, 3)->setTime(12, 0));

        [$tenant, $user] = $this->tenantAndUser('dashboard-owner', 'dashboard-owner@example.com');
        [$otherTenant] = $this->tenantAndUser('dashboard-other', 'dashboard-other@example.com');

        $column = $this->column($tenant);
        $customer = $this->customer($tenant);
        $otherColumn = $this->column($otherTenant);
        $otherCustomer = $this->customer($otherTenant);

        $this->serviceOrder($tenant, $customer, $column, [
            'status' => 'finished',
            'total_price' => 100,
            'total_cost' => 25,
            'updated_at' => now(),
        ]);

        $this->serviceOrder($otherTenant, $otherCustomer, $otherColumn, [
            'status' => 'finished',
            'total_price' => 999,
            'total_cost' => 0,
            'updated_at' => now(),
        ]);

        Item::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Estoque de outra assistência',
            'type' => 'supply',
            'is_for_sale' => false,
            'cost_price' => 1,
            'sale_price' => 2,
            'stock_quantity' => 0,
            'min_stock_alert' => 5,
        ]);

        $this->actingAs($user)
            ->get(route('tenant.dashboard', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('R$ 75,00')
            ->assertDontSee('R$ 999,00')
            ->assertDontSee('Estoque de outra assistência');
    }

    private function tenantAndUser(string $slug, string $email): array
    {
        $plan = Plan::create([
            'name' => 'Plano '.$slug,
            'price_monthly' => 99,
            'features' => ['dashboard' => true],
            'trial_days_allowed' => 7,
        ]);

        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'name' => 'Assistencia '.$slug,
            'slug' => $slug,
            'document' => substr(md5($slug), 0, 14),
            'status' => 'active',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dashboard User',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        return [$tenant, $user];
    }

    private function column(Tenant $tenant): KanbanColumn
    {
        return KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pronto',
            'order_index' => 1,
        ]);
    }

    private function customer(Tenant $tenant): Customer
    {
        return Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente Dashboard',
            'cpf' => substr(md5($tenant->slug), 0, 11),
        ]);
    }

    private function serviceOrder(Tenant $tenant, Customer $customer, KanbanColumn $column, array $overrides = []): ServiceOrder
    {
        return ServiceOrder::create(array_merge([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'kanban_position' => 1,
            'device_model' => 'Notebook',
            'defect_symptoms' => 'Sem imagem',
            'status' => 'pending',
            'total_cost' => 0,
            'total_price' => 0,
            'deadline_at' => now()->addDay(),
        ], $overrides));
    }
}
