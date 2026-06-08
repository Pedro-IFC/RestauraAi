<?php

namespace Tests\Feature;

use App\Models\CheckoutOrder;
use App\Models\CheckoutCart;
use App\Models\Customer;
use App\Models\Item;
use App\Models\KanbanColumn;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicCheckoutSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_open_product_order_with_split_without_stock_decrement(): void
    {
        $tenant = $this->tenant('checkout-produtos');
        $item = $this->item($tenant, 'Tela LCD', 3, 200);
        [$user, $customer] = $this->customer($tenant, 'checkout-produtos@example.com');

        $this->post('/checkout-produtos/checkout/carrinho', [
            'item_id' => $item->id,
            'quantity' => 2,
        ])
            ->assertRedirect(route('public.checkout.cart', $tenant->slug));

        $this->actingAs($user)
            ->get('/checkout-produtos/checkout')
            ->assertOk()
            ->assertSee('Tela LCD')
            ->assertSee('R$ 400,00')
            ->assertSee('R$ 40,00')
            ->assertSee('R$ 360,00');

        $this->actingAs($user)->post('/checkout-produtos/checkout', [
            'payment_method' => 'pix',
        ])->assertRedirect();

        $order = CheckoutOrder::with('items')->firstOrFail();

        $this->assertSame('open', $order->status);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame('products', $order->type);
        $this->assertSame('pix', $order->payment_method);
        $this->assertSame('400.00', (string) $order->total_amount);
        $this->assertSame('40.00', (string) $order->fixgo_commission_amount);
        $this->assertSame('360.00', (string) $order->tenant_amount);
        $this->assertCount(1, $order->items);
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'stock_quantity' => 3,
        ]);
    }

    public function test_guest_must_login_to_finish_product_checkout(): void
    {
        $tenant = $this->tenant('checkout-login');
        $item = $this->item($tenant, 'Bateria', 2, 150);

        $this->post('/checkout-login/checkout/carrinho', [
            'item_id' => $item->id,
            'quantity' => 1,
        ]);

        $this->post('/checkout-login/checkout', [
            'payment_method' => 'pix',
        ])->assertRedirect(route('public.customer.login', [
            'slug' => $tenant->slug,
            'intended' => '/checkout-login/checkout',
        ]));

        $this->assertDatabaseCount('checkout_orders', 0);
    }

    public function test_open_checkout_order_can_be_canceled(): void
    {
        $tenant = $this->tenant('checkout-cancelar');
        $order = CheckoutOrder::create([
            'tenant_id' => $tenant->id,
            'type' => 'products',
            'status' => 'open',
            'payment_method' => 'card',
            'total_amount' => 100,
            'fixgo_commission_rate' => 10,
            'fixgo_commission_amount' => 10,
            'tenant_amount' => 90,
        ]);

        $this->patch('/checkout-cancelar/checkout/pedidos/'.$order->id.'/cancelar')
            ->assertRedirect(route('public.checkout.order.show', [$tenant->slug, $order]));

        $order->refresh();

        $this->assertSame('canceled', $order->status);
        $this->assertNotNull($order->canceled_at);
    }

    public function test_customer_can_create_open_budget_payment_order_after_approval(): void
    {
        $tenant = $this->tenant('checkout-orcamento');
        [$user, $customer] = $this->customer($tenant, 'orcamento@example.com');
        $customer->update([
            'name' => 'Cliente Orçamento',
            'cpf' => '123.123.123-12',
        ]);
        $column = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Orçamento',
            'order_index' => 1,
        ]);
        $serviceOrder = ServiceOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'device_model' => 'MacBook',
            'defect_symptoms' => 'Sem imagem.',
            'status' => 'approved',
            'total_cost' => 200,
            'total_price' => 500,
        ]);

        $this->actingAs($user)->post('/checkout-orcamento/checkout/orcamento/'.$serviceOrder->id, [
            'payment_method' => 'card',
        ])->assertRedirect();

        $order = CheckoutOrder::with('items')->firstOrFail();

        $this->assertSame('service_order_budget', $order->type);
        $this->assertSame('open', $order->status);
        $this->assertSame($serviceOrder->id, $order->service_order_id);
        $this->assertSame('500.00', (string) $order->total_amount);
        $this->assertSame('50.00', (string) $order->fixgo_commission_amount);
        $this->assertSame('450.00', (string) $order->tenant_amount);
        $this->assertSame('Orçamento aprovado - OS #'.$serviceOrder->id, $order->items->first()->description);
    }

    public function test_authenticated_customer_has_isolated_persistent_carts_per_tenant(): void
    {
        $tenant = $this->tenant('carrinho-ph');
        $otherTenant = $this->tenant('carrinho-consoles');
        $mouse = $this->item($tenant, 'Mouse PH', 5, 80);
        $joystick = $this->item($otherTenant, 'Controle Console', 4, 220);
        [$user] = $this->customer($tenant, 'carrinhos@example.com');
        $this->customer($otherTenant, 'carrinhos@example.com', $user);

        $this->actingAs($user)
            ->post('/carrinho-ph/checkout/carrinho', [
                'item_id' => $mouse->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('public.checkout.cart', $tenant->slug));

        $this->actingAs($user)
            ->post('/carrinho-consoles/checkout/carrinho', [
                'item_id' => $joystick->id,
                'quantity' => 1,
            ])
            ->assertRedirect(route('public.checkout.cart', $otherTenant->slug));

        $this->assertSame(2, CheckoutCart::where('user_id', $user->id)->count());

        $this->actingAs($user)
            ->get('/carrinho-ph/checkout')
            ->assertOk()
            ->assertSee('Mouse PH')
            ->assertDontSee('Controle Console');

        $this->actingAs($user)
            ->get('/carrinho-consoles/checkout')
            ->assertOk()
            ->assertSee('Controle Console')
            ->assertDontSee('Mouse PH');

        $this->actingAs($user)
            ->post('/carrinho-consoles/checkout', [
                'payment_method' => 'pix',
            ])
            ->assertRedirect();

        $this->assertSame(1, CheckoutCart::where('user_id', $user->id)->where('tenant_id', $tenant->id)->firstOrFail()->items()->count());
        $this->assertSame(0, CheckoutCart::where('user_id', $user->id)->where('tenant_id', $otherTenant->id)->firstOrFail()->items()->count());
    }

    public function test_checkout_cart_rejects_product_from_another_tenant(): void
    {
        $tenant = $this->tenant('carrinho-certo');
        $otherTenant = $this->tenant('carrinho-errado');
        $foreignItem = $this->item($otherTenant, 'Produto de outro CNPJ', 3, 99);
        [$user] = $this->customer($tenant, 'seguranca-carrinho@example.com');

        $this->actingAs($user)
            ->post('/carrinho-certo/checkout/carrinho', [
                'item_id' => $foreignItem->id,
                'quantity' => 1,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('checkout_cart_items', 0);
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

    private function item(Tenant $tenant, string $name, int $stock, int $price): Item
    {
        return Item::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'type' => 'product',
            'is_for_sale' => true,
            'cost_price' => 80,
            'sale_price' => $price,
            'stock_quantity' => $stock,
            'min_stock_alert' => 1,
        ]);
    }

    private function customer(Tenant $tenant, string $email, ?User $user = null): array
    {
        $user ??= User::create([
            'name' => 'Cliente Checkout',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'tenant_id' => null,
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        return [$user, $customer];
    }
}
