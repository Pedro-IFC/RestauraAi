<?php

namespace Tests\Feature;

use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantCheckoutOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_list_catalog_orders_and_open_chronological_history(): void
    {
        [$tenant, $user] = $this->tenantAndUser('pedidos-historico');
        $customer = $this->customer($tenant);
        $order = $this->order($tenant, $customer);
        $order->transitionTo(CheckoutOrder::STATE_AWAITING_PAYMENT, $user, 'Pedido recebido.');
        $order->transitionTo(CheckoutOrder::STATE_PAID, $user, 'Split confirmado.');

        $this->actingAs($user)
            ->get(route('tenant.checkout-orders.index'))
            ->assertOk()
            ->assertSee('Pedidos do catalogo')
            ->assertSee('Pedido')
            ->assertSee('#'.$order->id)
            ->assertSee('Pago');

        $this->actingAs($user)
            ->get(route('tenant.checkout-orders.show', $order))
            ->assertOk()
            ->assertSee('Historico cronologico')
            ->assertSee('Aguardando pagamento')
            ->assertSee('Split confirmado.')
            ->assertSee($user->name);
    }

    public function test_tenant_can_update_status_and_audit_who_changed_it(): void
    {
        [$tenant, $user] = $this->tenantAndUser('pedidos-status');
        $order = $this->order($tenant, $this->customer($tenant));
        $order->transitionTo(CheckoutOrder::STATE_AWAITING_PAYMENT, null, 'Pedido criado.');

        $this->actingAs($user)
            ->patch(route('tenant.checkout-orders.status', $order), [
                'state' => CheckoutOrder::STATE_PREPARING,
                'reason' => 'Separando e embalando produto fisico.',
            ])
            ->assertRedirect(route('tenant.checkout-orders.show', $order));

        $order->refresh();

        $this->assertSame(CheckoutOrder::STATUS_PAID, $order->status);
        $this->assertSame(CheckoutOrder::FULFILLMENT_PREPARING, $order->fulfillment_status);
        $this->assertDatabaseHas('checkout_order_status_histories', [
            'checkout_order_id' => $order->id,
            'user_id' => $user->id,
            'from_state' => CheckoutOrder::STATE_AWAITING_PAYMENT,
            'to_state' => CheckoutOrder::STATE_PREPARING,
            'reason' => 'Separando e embalando produto fisico.',
        ]);
    }

    public function test_refused_or_canceled_orders_require_reason(): void
    {
        [$tenant, $user] = $this->tenantAndUser('pedidos-motivo');
        $order = $this->order($tenant, $this->customer($tenant));

        $this->actingAs($user)
            ->from(route('tenant.checkout-orders.show', $order))
            ->patch(route('tenant.checkout-orders.status', $order), [
                'state' => CheckoutOrder::STATE_REFUSED,
            ])
            ->assertRedirect(route('tenant.checkout-orders.show', $order))
            ->assertSessionHasErrors('reason');

        $this->actingAs($user)
            ->patch(route('tenant.checkout-orders.status', $order), [
                'state' => CheckoutOrder::STATE_REFUSED,
                'reason' => 'Compra recusada por falta de estoque real.',
            ])
            ->assertRedirect(route('tenant.checkout-orders.show', $order));

        $order->refresh();

        $this->assertSame(CheckoutOrder::STATUS_REFUSED, $order->status);
        $this->assertNotNull($order->canceled_at);
        $this->assertDatabaseHas('checkout_order_status_histories', [
            'checkout_order_id' => $order->id,
            'to_state' => CheckoutOrder::STATE_REFUSED,
            'reason' => 'Compra recusada por falta de estoque real.',
        ]);
    }

    public function test_tenant_cannot_access_another_tenants_catalog_order(): void
    {
        [$tenant, $user] = $this->tenantAndUser('pedidos-isolado');
        [$otherTenant] = $this->tenantAndUser('pedidos-outro');
        $otherOrder = $this->order($otherTenant, $this->customer($otherTenant));

        $this->actingAs($user)
            ->get(route('tenant.checkout-orders.show', $otherOrder))
            ->assertNotFound();
    }

    private function tenantAndUser(string $slug): array
    {
        $plan = Plan::create([
            'name' => 'Plano '.$slug,
            'price_monthly' => 99,
            'features' => Plan::presetFeatures('ouro'),
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
            'name' => 'Atendente '.$slug,
            'email' => $slug.'@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        return [$tenant, $user];
    }

    private function customer(Tenant $tenant): Customer
    {
        return Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente Pedido',
            'email' => 'cliente-'.$tenant->slug.'@example.com',
        ]);
    }

    private function order(Tenant $tenant, Customer $customer): CheckoutOrder
    {
        $order = CheckoutOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'type' => 'products',
            'status' => CheckoutOrder::STATUS_OPEN,
            'payment_method' => 'pix',
            'fulfillment_method' => 'pickup',
            'fulfillment_status' => CheckoutOrder::FULFILLMENT_PENDING,
            'total_amount' => 200,
            'fixgo_commission_rate' => 10,
            'fixgo_commission_amount' => 20,
            'tenant_amount' => 180,
        ]);

        $order->items()->create([
            'description' => 'Carregador USB-C',
            'quantity' => 1,
            'unit_price' => 200,
            'total_price' => 200,
        ]);

        return $order;
    }
}
