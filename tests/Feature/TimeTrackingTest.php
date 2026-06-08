<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\KanbanColumn;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_technician_can_start_and_pause_service_order_timer(): void
    {
        [$tenant, $user, $serviceOrder] = $this->serviceOrderFixture();

        $this->travelTo(now()->setTime(10, 0));

        $this->actingAs($user)
            ->post(route('tenant.tracking.start', $serviceOrder))
            ->assertRedirect();

        $this->assertDatabaseHas('time_entries', [
            'service_order_id' => $serviceOrder->id,
            'user_id' => $user->id,
            'ended_at' => null,
            'duration_seconds' => 0,
        ]);

        $this->travel(15)->minutes();

        $this->actingAs($user)
            ->post(route('tenant.tracking.stop', $serviceOrder))
            ->assertRedirect();

        $entry = TimeEntry::where('service_order_id', $serviceOrder->id)->firstOrFail();

        $this->assertNotNull($entry->ended_at);
        $this->assertSame(900, $entry->duration_seconds);
        $this->assertSame(900, $serviceOrder->fresh()->trackedSeconds());
        $this->assertSame($tenant->id, $serviceOrder->tenant_id);
    }

    public function test_technician_cannot_start_duplicate_running_timer_for_same_service_order(): void
    {
        [, $user, $serviceOrder] = $this->serviceOrderFixture();

        $this->actingAs($user)
            ->post(route('tenant.tracking.start', $serviceOrder))
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('tenant.tracking.start', $serviceOrder))
            ->assertRedirect();

        $this->assertSame(1, TimeEntry::where('service_order_id', $serviceOrder->id)->running()->count());
    }

    public function test_technician_cannot_track_service_order_from_another_tenant(): void
    {
        [, , $serviceOrder] = $this->serviceOrderFixture();
        [, $otherUser] = $this->tenantAndUser('outra-assistencia', 'other@example.com');

        $this->actingAs($otherUser)
            ->post(route('tenant.tracking.start', $serviceOrder))
            ->assertNotFound();

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_technician_can_add_manual_time_to_service_order(): void
    {
        [, $user, $serviceOrder] = $this->serviceOrderFixture();
        $startedAt = now()->setTime(8, 30);

        $this->actingAs($user)
            ->post(route('tenant.tracking.manual', $serviceOrder), [
                'started_at' => $startedAt->format('Y-m-d H:i:s'),
                'hours' => 1,
                'minutes' => 25,
            ])
            ->assertRedirect();

        $entry = TimeEntry::where('service_order_id', $serviceOrder->id)->firstOrFail();

        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame($startedAt->format('Y-m-d H:i'), $entry->started_at->format('Y-m-d H:i'));
        $this->assertSame($startedAt->copy()->addMinutes(85)->format('Y-m-d H:i'), $entry->ended_at->format('Y-m-d H:i'));
        $this->assertSame(5100, $entry->duration_seconds);
        $this->assertSame(5100, $serviceOrder->fresh()->trackedSeconds());
    }

    public function test_manual_time_requires_positive_duration(): void
    {
        [, $user, $serviceOrder] = $this->serviceOrderFixture();

        $this->actingAs($user)
            ->from(route('ordens-servico.show', $serviceOrder))
            ->post(route('tenant.tracking.manual', $serviceOrder), [
                'started_at' => now()->format('Y-m-d H:i:s'),
                'hours' => 0,
                'minutes' => 0,
            ])
            ->assertRedirect(route('ordens-servico.show', $serviceOrder))
            ->assertSessionHasErrors('duration');

        $this->assertDatabaseCount('time_entries', 0);
    }

    public function test_manual_time_is_scoped_to_authenticated_tenant(): void
    {
        [, , $serviceOrder] = $this->serviceOrderFixture();
        [, $otherUser] = $this->tenantAndUser('manual-outra-assistencia', 'manual-other@example.com');

        $this->actingAs($otherUser)
            ->post(route('tenant.tracking.manual', $serviceOrder), [
                'started_at' => now()->format('Y-m-d H:i:s'),
                'hours' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('time_entries', 0);
    }

    private function serviceOrderFixture(): array
    {
        [$tenant, $user] = $this->tenantAndUser('assistencia-teste', 'tecnico-time@example.com');

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente',
            'cpf' => '123',
        ]);

        $column = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Na Bancada',
            'order_index' => 1,
        ]);

        $serviceOrder = ServiceOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'kanban_position' => 1,
            'device_model' => 'Notebook',
            'defect_symptoms' => 'Não liga',
            'status' => 'pending',
            'total_cost' => 0,
            'total_price' => 0,
        ]);

        return [$tenant, $user, $serviceOrder];
    }

    private function tenantAndUser(string $slug, string $email): array
    {
        $plan = Plan::create([
            'name' => 'Plano '.$slug,
            'price_monthly' => 99,
            'features' => ['time_tracking' => true],
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
            'name' => 'Tecnico',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        return [$tenant, $user];
    }
}
