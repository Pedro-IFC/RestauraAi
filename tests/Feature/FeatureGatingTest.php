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

class FeatureGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_bronze_plan_only_allows_kanban_and_blocks_paid_features(): void
    {
        [$tenant, $user] = $this->tenantAndUser('bronze', Plan::presetFeatures('bronze'));
        $serviceOrder = $this->serviceOrder($tenant);

        $this->actingAs($user)
            ->get(route('tenant.kanban.index'))
            ->assertOk()
            ->assertSee('Kanban')
            ->assertDontSee('Estoque')
            ->assertDontSee('Customização');

        $this->actingAs($user)
            ->get(route('estoque.index'))
            ->assertRedirect(route('tenant.plan.blocked'));

        $this->actingAs($user)
            ->post(route('tenant.tracking.start', $serviceOrder))
            ->assertRedirect(route('tenant.plan.blocked'));

        $this->actingAs($user)
            ->get(route('tenant.dashboard'))
            ->assertRedirect(route('tenant.kanban.index'));
    }

    public function test_bronze_plan_enforces_monthly_service_order_limit(): void
    {
        [$tenant, $user] = $this->tenantAndUser('bronze-limit', Plan::presetFeatures('bronze'));
        $customer = $this->customer($tenant);
        $column = $this->column($tenant);

        for ($i = 0; $i < 50; $i++) {
            $this->serviceOrder($tenant, [
                'customer_id' => $customer->id,
                'kanban_column_id' => $column->id,
                'device_model' => 'Aparelho '.$i,
            ]);
        }

        $this->actingAs($user)
            ->post(route('ordens-servico.store'), [
                'customer_id' => $customer->id,
                'kanban_column_id' => $column->id,
                'device_model' => 'Aparelho bloqueado',
                'defect_symptoms' => 'Sem imagem',
            ])
            ->assertSessionHasErrors('plan');
    }

    public function test_prata_plan_allows_inventory_catalog_and_basic_customization_but_blocks_advanced_features(): void
    {
        [, $user] = $this->tenantAndUser('prata', Plan::presetFeatures('prata'));

        $this->actingAs($user)
            ->get(route('estoque.index'))
            ->assertOk()
            ->assertSee('Kanban')
            ->assertSee('Estoque')
            ->assertSee('Agenda')
            ->assertSee('Customização');

        $this->actingAs($user)
            ->get(route('tenant.customization.edit'))
            ->assertOk()
            ->assertSee('exigem personalização avançada');

        $this->actingAs($user)
            ->get(route('tenant.dashboard'))
            ->assertRedirect(route('tenant.kanban.index'));
    }

    public function test_ouro_plan_allows_dashboard_time_tracking_and_advanced_customization(): void
    {
        [$tenant, $user] = $this->tenantAndUser('ouro', Plan::presetFeatures('ouro'));
        $serviceOrder = $this->serviceOrder($tenant);

        $this->actingAs($user)
            ->get(route('tenant.dashboard'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('tenant.tracking.start', $serviceOrder))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('tenant.customization.edit'))
            ->assertOk()
            ->assertDontSee('exigem personalização avançada');
    }

    private function tenantAndUser(string $slug, array $features): array
    {
        $plan = Plan::create([
            'name' => 'Plano '.$slug,
            'price_monthly' => 99,
            'features' => $features,
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
            'name' => 'Feature User',
            'email' => $slug.'@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        return [$tenant, $user];
    }

    private function column(Tenant $tenant): KanbanColumn
    {
        return KanbanColumn::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Triagem',
            ],
            ['order_index' => 1]
        );
    }

    private function customer(Tenant $tenant): Customer
    {
        return Customer::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cpf' => substr(md5($tenant->slug), 0, 11),
            ],
            ['name' => 'Cliente Feature']
        );
    }

    private function serviceOrder(Tenant $tenant, array $overrides = []): ServiceOrder
    {
        $customer = isset($overrides['customer_id'])
            ? Customer::findOrFail($overrides['customer_id'])
            : $this->customer($tenant);
        $column = isset($overrides['kanban_column_id'])
            ? KanbanColumn::findOrFail($overrides['kanban_column_id'])
            : $this->column($tenant);

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
        ], $overrides));
    }
}
