<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Item;
use App\Models\KanbanColumn;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_only_shows_items_explicitly_marked_for_sale(): void
    {
        $tenant = $this->tenant();

        Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tela LCD',
            'type' => 'product',
            'is_for_sale' => true,
            'cost_price' => 80,
            'sale_price' => 180,
            'stock_quantity' => 3,
            'min_stock_alert' => 1,
        ]);

        Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Fita Kapton',
            'type' => 'supply',
            'is_for_sale' => false,
            'cost_price' => 2,
            'sale_price' => 5,
            'stock_quantity' => 20,
            'min_stock_alert' => 2,
        ]);

        Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cabo USB oculto',
            'type' => 'product',
            'is_for_sale' => false,
            'cost_price' => 10,
            'sale_price' => 30,
            'stock_quantity' => 5,
            'min_stock_alert' => 1,
        ]);

        $this->get('/'.$tenant->slug)
            ->assertOk()
            ->assertSee('Tela LCD')
            ->assertDontSee('Fita Kapton')
            ->assertDontSee('Cabo USB oculto');
    }

    public function test_attaching_item_to_service_order_decrements_stock_and_recalculates_margin(): void
    {
        $tenant = $this->tenant();
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tecnico',
            'email' => 'tecnico@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente',
            'cpf' => '123',
        ]);
        $column = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Na bancada',
            'order_index' => 1,
        ]);
        $serviceOrder = ServiceOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'device_model' => 'Notebook',
            'defect_symptoms' => 'Aquecimento',
            'status' => 'budgeting',
            'total_cost' => 0,
            'total_price' => 0,
        ]);
        $item = Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pasta térmica',
            'type' => 'supply',
            'is_for_sale' => false,
            'cost_price' => 4,
            'sale_price' => 15,
            'stock_quantity' => 10,
            'min_stock_alert' => 2,
        ]);

        $this->actingAs($user)
            ->post(route('tenant.os.attach_items', $serviceOrder), [
                'item_id' => $item->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('ordens-servico.show', $serviceOrder));

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'stock_quantity' => 8,
        ]);
        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'total_cost' => 8,
            'total_price' => 30,
        ]);
    }

    public function test_item_detail_shows_service_order_history_without_ambiguous_created_at(): void
    {
        $tenant = $this->tenant();
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tecnico',
            'email' => 'historico@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente',
            'cpf' => '456',
        ]);
        $column = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Na bancada',
            'order_index' => 1,
        ]);
        $serviceOrder = ServiceOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'device_model' => 'MacBook',
            'defect_symptoms' => 'Sem vídeo',
            'status' => 'finished',
            'total_cost' => 0,
            'total_price' => 0,
        ]);
        $item = Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tela LCD',
            'type' => 'product',
            'is_for_sale' => true,
            'cost_price' => 100,
            'sale_price' => 250,
            'stock_quantity' => 2,
            'min_stock_alert' => 1,
        ]);

        $serviceOrder->items()->attach($item->id, [
            'quantity' => 1,
            'unit_cost' => 100,
            'unit_price' => 250,
        ]);

        $this->actingAs($user)
            ->get(route('estoque.show', $item))
            ->assertOk()
            ->assertSee('OS #'.$serviceOrder->id)
            ->assertSee('MacBook');
    }

    private function tenant(): Tenant
    {
        $plan = Plan::create([
            'name' => 'Plano Teste',
            'price_monthly' => 99,
            'features' => Plan::presetFeatures('ouro'),
            'trial_days_allowed' => 7,
        ]);

        return Tenant::create([
            'plan_id' => $plan->id,
            'name' => 'Assistencia Teste',
            'slug' => 'assistencia-teste',
            'document' => '00.000.000/0001-00',
            'status' => 'active',
        ]);
    }
}
