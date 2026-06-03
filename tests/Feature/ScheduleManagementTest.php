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

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_shows_deadline_alerts_and_calendar_orders(): void
    {
        [$tenant, $user] = $this->tenantAndUser('agenda-teste', 'agenda@example.com');
        $column = $this->column($tenant);
        $customer = $this->customer($tenant);

        $overdueOrder = $this->serviceOrder($tenant, $customer, $column, [
            'device_model' => 'Console vencido',
            'deadline_at' => now()->subDay(),
        ]);
        $todayOrder = $this->serviceOrder($tenant, $customer, $column, [
            'device_model' => 'Notebook hoje',
            'deadline_at' => now()->setTime(17, 0),
        ]);

        $this->actingAs($user)
            ->get(route('tenant.schedule.index', [
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Prazos vencidos')
            ->assertSee('Vencem hoje')
            ->assertSee('Console vencido')
            ->assertSee('Notebook hoje')
            ->assertSee('OS #'.$overdueOrder->id)
            ->assertSee('OS #'.$todayOrder->id);
    }

    public function test_schedule_can_update_deadline_and_hardware_receipt(): void
    {
        [$tenant, $user] = $this->tenantAndUser('agenda-update', 'agenda-update@example.com');
        $column = $this->column($tenant);
        $customer = $this->customer($tenant);
        $serviceOrder = $this->serviceOrder($tenant, $customer, $column, [
            'deadline_at' => now()->addDay(),
        ]);

        $deadline = now()->addDays(3)->setTime(15, 30);
        $receivedAt = now()->setTime(9, 45);

        $this->actingAs($user)
            ->patch(route('tenant.schedule.service_orders.update', $serviceOrder), [
                'deadline_at' => $deadline->format('Y-m-d H:i:s'),
                'hardware_received_at' => $receivedAt->format('Y-m-d H:i:s'),
                'hardware_received_notes' => 'Recebido com carregador e capa.',
            ])
            ->assertRedirect(route('tenant.schedule.index'));

        $serviceOrder->refresh();

        $this->assertSame($deadline->format('Y-m-d H:i'), $serviceOrder->deadline_at->format('Y-m-d H:i'));
        $this->assertSame($receivedAt->format('Y-m-d H:i'), $serviceOrder->hardware_received_at->format('Y-m-d H:i'));
        $this->assertSame('Recebido com carregador e capa.', $serviceOrder->hardware_received_notes);
    }

    public function test_schedule_update_is_scoped_to_authenticated_tenant(): void
    {
        [$tenant] = $this->tenantAndUser('agenda-owner', 'agenda-owner@example.com');
        [, $otherUser] = $this->tenantAndUser('agenda-other', 'agenda-other@example.com');
        $column = $this->column($tenant);
        $customer = $this->customer($tenant);
        $serviceOrder = $this->serviceOrder($tenant, $customer, $column, [
            'deadline_at' => now()->addDay(),
        ]);

        $this->actingAs($otherUser)
            ->patch(route('tenant.schedule.service_orders.update', $serviceOrder), [
                'mark_hardware_received' => 1,
            ])
            ->assertNotFound();

        $this->assertNull($serviceOrder->fresh()->hardware_received_at);
    }

    private function tenantAndUser(string $slug, string $email): array
    {
        $plan = Plan::create([
            'name' => 'Plano '.$slug,
            'price_monthly' => 99,
            'features' => ['schedule' => true],
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
            'name' => 'Agenda User',
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
            'name' => 'Triagem',
            'order_index' => 1,
        ]);
    }

    private function customer(Tenant $tenant): Customer
    {
        return Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente Agenda',
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
