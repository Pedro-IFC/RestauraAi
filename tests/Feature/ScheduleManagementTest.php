<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\KanbanColumn;
use App\Models\Plan;
use App\Models\ScheduleEvent;
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
        $plannedStart = now()->addDays(2)->setTime(10, 15);
        $receivedAt = now()->setTime(9, 45);

        $this->actingAs($user)
            ->patch(route('tenant.schedule.service_orders.update', $serviceOrder), [
                'planned_start_at' => $plannedStart->format('Y-m-d H:i:s'),
                'deadline_at' => $deadline->format('Y-m-d H:i:s'),
                'hardware_received_at' => $receivedAt->format('Y-m-d H:i:s'),
                'hardware_received_notes' => 'Recebido com carregador e capa.',
                'schedule_notes' => 'Separar bancada e aguardar peça.',
            ])
            ->assertRedirect(route('tenant.schedule.index'));

        $serviceOrder->refresh();

        $this->assertSame($plannedStart->format('Y-m-d H:i'), $serviceOrder->planned_start_at->format('Y-m-d H:i'));
        $this->assertSame($deadline->format('Y-m-d H:i'), $serviceOrder->deadline_at->format('Y-m-d H:i'));
        $this->assertSame($receivedAt->format('Y-m-d H:i'), $serviceOrder->hardware_received_at->format('Y-m-d H:i'));
        $this->assertSame('Recebido com carregador e capa.', $serviceOrder->hardware_received_notes);
        $this->assertSame('Separar bancada e aguardar peça.', $serviceOrder->schedule_notes);
    }

    public function test_schedule_can_create_independent_events_and_reminders(): void
    {
        [, $user] = $this->tenantAndUser('agenda-eventos', 'agenda-eventos@example.com');
        $startsAt = now()->addDay()->setTime(14, 0);
        $remindAt = now()->subHour();

        $this->actingAs($user)
            ->post(route('tenant.schedule.events.store'), [
                'title' => 'Reunião com fornecedor',
                'type' => ScheduleEvent::TYPE_MEETING,
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'remind_at' => $remindAt->format('Y-m-d H:i:s'),
                'location' => 'Sala técnica',
                'notes' => 'Alinhar recebimento de pedido.',
            ])
            ->assertRedirect(route('tenant.schedule.index'));

        $event = ScheduleEvent::firstOrFail();

        $this->assertSame('Reunião com fornecedor', $event->title);
        $this->assertNull($event->service_order_id);

        $this->actingAs($user)
            ->get(route('tenant.schedule.index', [
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Reunião com fornecedor')
            ->assertSee('Lembretes pendentes')
            ->assertSee('Alinhar recebimento de pedido.');

        $this->actingAs($user)
            ->patch(route('tenant.schedule.events.update', $event), [
                'complete' => 1,
            ])
            ->assertRedirect(route('tenant.schedule.index'));

        $this->assertNotNull($event->fresh()->completed_at);
    }

    public function test_schedule_event_can_be_linked_to_service_order_and_is_tenant_scoped(): void
    {
        [$tenant, $user] = $this->tenantAndUser('agenda-os-evento', 'agenda-os-evento@example.com');
        [, $otherUser] = $this->tenantAndUser('agenda-os-outro', 'agenda-os-outro@example.com');
        $column = $this->column($tenant);
        $customer = $this->customer($tenant);
        $serviceOrder = $this->serviceOrder($tenant, $customer, $column);

        $this->actingAs($user)
            ->post(route('tenant.schedule.events.store'), [
                'title' => 'Recebimento de pedido de tela',
                'type' => ScheduleEvent::TYPE_ORDER_RECEIPT,
                'service_order_id' => $serviceOrder->id,
                'starts_at' => now()->addDay()->setTime(11, 0)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('tenant.schedule.index'));

        $event = ScheduleEvent::firstOrFail();

        $this->assertSame($serviceOrder->id, $event->service_order_id);

        $this->actingAs($otherUser)
            ->delete(route('tenant.schedule.events.destroy', $event))
            ->assertNotFound();

        $this->assertDatabaseHas('schedule_events', [
            'id' => $event->id,
            'tenant_id' => $tenant->id,
        ]);
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

    public function test_hardware_receipt_advances_service_order_to_next_kanban_stage(): void
    {
        [$tenant, $user] = $this->tenantAndUser('agenda-recebimento-fluxo', 'agenda-fluxo@example.com');
        $triage = $this->column($tenant);
        $budget = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Orçamento',
            'order_index' => 2,
        ]);
        $customer = $this->customer($tenant);
        $serviceOrder = $this->serviceOrder($tenant, $customer, $triage, [
            'hardware_received_at' => null,
            'kanban_position' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('tenant.schedule.service_orders.update', $serviceOrder), [
                'mark_hardware_received' => 1,
                'hardware_received_notes' => 'Recebido no balcão.',
            ])
            ->assertRedirect(route('tenant.schedule.index'));

        $serviceOrder->refresh();

        $this->assertNotNull($serviceOrder->hardware_received_at);
        $this->assertSame('Recebido no balcão.', $serviceOrder->hardware_received_notes);
        $this->assertSame($budget->id, $serviceOrder->kanban_column_id);
        $this->assertSame('budgeting', $serviceOrder->status);
    }

    public function test_hardware_receipt_can_follow_selected_kanban_stage(): void
    {
        [$tenant, $user] = $this->tenantAndUser('agenda-recebimento-destino', 'agenda-destino@example.com');
        $triage = $this->column($tenant);
        KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Orçamento',
            'order_index' => 2,
        ]);
        $bench = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Na Bancada',
            'order_index' => 3,
        ]);
        $customer = $this->customer($tenant);
        $serviceOrder = $this->serviceOrder($tenant, $customer, $triage, [
            'hardware_received_at' => null,
            'kanban_position' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('tenant.schedule.service_orders.update', $serviceOrder), [
                'mark_hardware_received' => 1,
                'next_kanban_column_id' => $bench->id,
            ])
            ->assertRedirect(route('tenant.schedule.index'));

        $this->assertSame($bench->id, $serviceOrder->fresh()->kanban_column_id);
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
