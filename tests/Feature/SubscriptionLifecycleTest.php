<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_list_and_manage_tenant_subscription_status(): void
    {
        $superadmin = $this->superadmin();
        $tenant = $this->tenant('assinatura-teste', 'active');

        $this->actingAs($superadmin)
            ->get(route('admin.tenants.index'))
            ->assertOk()
            ->assertSee('Assistencia assinatura-teste')
            ->assertSee('Ativa');

        $this->actingAs($superadmin)
            ->patch(route('admin.subscriptions.billing', $tenant), [
                'status' => 'suspended',
                'payment_overdue_since' => now()->subDays(10)->toDateString(),
                'payment_grace_days' => 7,
                'subscription_notes' => 'Pagamento atrasado no gateway.',
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame('suspended', $tenant->status);
        $this->assertNotNull($tenant->suspended_at);
        $this->assertSame('Pagamento atrasado no gateway.', $tenant->subscription_notes);
    }

    public function test_overdue_tenant_is_auto_suspended_and_redirected_to_billing_screen(): void
    {
        $tenant = $this->tenant('inadimplente-auto', 'active', [
            'payment_overdue_since' => now()->subDays(8),
            'payment_grace_days' => 7,
        ]);
        $user = $this->tenantUser($tenant);

        $this->actingAs($user)
            ->get(route('tenant.kanban.index'))
            ->assertRedirect(route('tenant.billing.index'));

        $this->assertSame('suspended', $tenant->fresh()->status);

        $this->actingAs($user)
            ->get(route('tenant.billing.index'))
            ->assertOk()
            ->assertSee('Assinatura bloqueada');
    }

    public function test_suspended_tenant_public_storefront_is_temporarily_offline(): void
    {
        $tenant = $this->tenant('catalogo-bloqueado', 'suspended');

        $this->get('/'.$tenant->slug)
            ->assertNotFound();
    }

    public function test_superadmin_can_reactivate_subscription_and_restore_public_storefront(): void
    {
        $superadmin = $this->superadmin();
        $tenant = $this->tenant('reativar-catalogo', 'suspended', [
            'payment_overdue_since' => now()->subDays(10),
            'payment_grace_days' => 7,
            'suspended_at' => now()->subDay(),
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.subscriptions.reactivate', $tenant))
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame('active', $tenant->status);
        $this->assertNull($tenant->payment_overdue_since);
        $this->assertNull($tenant->suspended_at);

        $this->get('/'.$tenant->slug)
            ->assertOk();
    }

    public function test_superadmin_can_create_new_tenant_with_plan_trial_days(): void
    {
        $this->travelTo(now()->setDate(2026, 6, 3)->setTime(10, 0));

        $superadmin = $this->superadmin();
        $plan = Plan::create([
            'name' => 'Plano Trial 14',
            'price_monthly' => 99,
            'features' => Plan::presetFeatures('ouro'),
            'trial_days_allowed' => 14,
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.tenants.store'), [
                'plan_id' => $plan->id,
                'name' => 'Assistência Trial',
                'slug' => 'assistencia-trial',
                'document' => '11.111.111/0001-11',
                'owner_name' => 'Dono Trial',
                'owner_email' => 'dono-trial@example.com',
                'owner_password' => 'senha123',
            ])
            ->assertRedirect();

        $tenant = Tenant::where('slug', 'assistencia-trial')->firstOrFail();

        $this->assertSame('trial', $tenant->status);
        $this->assertSame(now()->addDays(14)->endOfDay()->format('Y-m-d H:i'), $tenant->trial_ends_at->format('Y-m-d H:i'));
        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'dono-trial@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_new_tenant_without_plan_trial_starts_active(): void
    {
        $superadmin = $this->superadmin();
        $plan = Plan::create([
            'name' => 'Plano Sem Trial',
            'price_monthly' => 99,
            'features' => Plan::presetFeatures('ouro'),
            'trial_days_allowed' => 0,
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.tenants.store'), [
                'plan_id' => $plan->id,
                'name' => 'Assistência Ativa',
                'document' => '22.222.222/0001-22',
                'owner_name' => 'Dono Ativo',
                'owner_email' => 'dono-ativo@example.com',
                'owner_password' => 'senha123',
            ])
            ->assertRedirect();

        $tenant = Tenant::where('slug', 'assistencia-ativa')->firstOrFail();

        $this->assertSame('active', $tenant->status);
        $this->assertNull($tenant->trial_ends_at);
    }

    private function superadmin(): User
    {
        return User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin-subscriptions@example.com',
            'password' => 'password',
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);
    }

    private function tenant(string $slug, string $status, array $overrides = []): Tenant
    {
        $plan = Plan::create([
            'name' => 'Plano '.$slug,
            'price_monthly' => 99,
            'features' => Plan::presetFeatures('ouro'),
            'trial_days_allowed' => 7,
        ]);

        return Tenant::create(array_merge([
            'plan_id' => $plan->id,
            'name' => 'Assistencia '.$slug,
            'slug' => $slug,
            'document' => substr(md5($slug), 0, 14),
            'status' => $status,
            'payment_grace_days' => 7,
        ], $overrides));
    }

    private function tenantUser(Tenant $tenant): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant User',
            'email' => $tenant->slug.'@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }
}
