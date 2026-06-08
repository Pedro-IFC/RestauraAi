<?php

namespace Tests\Feature;

use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Item;
use App\Models\KanbanColumn;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicCustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_account_shows_equipment_orders_and_addresses_for_current_tenant(): void
    {
        [$tenant, $customer, $user] = $this->tenantCustomerAndUser('conta-cliente');
        [$otherTenant, $otherCustomer] = $this->tenantCustomerAndUser('outra-conta', $user);

        $this->serviceOrder($tenant, $customer, 'iPhone 14', 'Na Bancada');
        $this->serviceOrder($tenant, $customer, 'iPhone 14', 'Pronto');
        $this->serviceOrder($otherTenant, $otherCustomer, 'Notebook de outra loja', 'Triagem');

        $address = CustomerAddress::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'label' => 'Casa',
            'street' => 'Rua das Flores',
            'number' => '123',
            'city' => 'Blumenau',
            'state' => 'SC',
            'is_default' => true,
        ]);

        $order = CheckoutOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'type' => 'products',
            'status' => 'open',
            'payment_method' => 'pix',
            'fulfillment_method' => 'delivery',
            'fulfillment_status' => 'out_for_delivery',
            'total_amount' => 250,
            'fixgo_commission_rate' => 10,
            'fixgo_commission_amount' => 25,
            'tenant_amount' => 225,
        ]);
        $order->items()->create([
            'description' => 'Carregador USB-C',
            'quantity' => 1,
            'unit_price' => 250,
            'total_price' => 250,
        ]);

        $this->actingAs($user)
            ->get('/conta-cliente/minha-conta')
            ->assertOk()
            ->assertSee('Minha Conta')
            ->assertSee('iPhone 14')
            ->assertSee('2')
            ->assertSee('Pedido #'.$order->id)
            ->assertSee('Carregador USB-C')
            ->assertSee('Entrega - Saiu para entrega')
            ->assertSee('Rua das Flores')
            ->assertSee('Minhas Assistências')
            ->assertSee($tenant->name)
            ->assertSee($otherTenant->name)
            ->assertDontSee('Notebook de outra loja');
    }

    public function test_authenticated_customer_can_switch_tenant_context_without_new_login(): void
    {
        [$tenant, $customer, $user] = $this->tenantCustomerAndUser('contexto-ph');
        [$otherTenant, $otherCustomer] = $this->tenantCustomerAndUser('contexto-consoles', $user);

        $this->serviceOrder($tenant, $customer, 'Notebook PH', 'Triagem');
        $this->serviceOrder($otherTenant, $otherCustomer, 'Console Switch', 'Triagem');

        $this->actingAs($user)
            ->get('/contexto-ph/minha-conta')
            ->assertOk()
            ->assertSee('Notebook PH')
            ->assertDontSee('Console Switch')
            ->assertSessionHas('public_tenant_slug', 'contexto-ph');

        $this->assertAuthenticatedAs($user);

        $this->actingAs($user)
            ->get('/contexto-consoles/minha-conta')
            ->assertOk()
            ->assertSee('Console Switch')
            ->assertDontSee('Notebook PH')
            ->assertSessionHas('public_tenant_slug', 'contexto-consoles');

        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_is_redirected_to_public_login_for_account(): void
    {
        $tenant = $this->tenant('login-conta');

        $this->get('/login-conta/minha-conta')
            ->assertRedirect(route('public.customer.login', [
                'slug' => $tenant->slug,
                'intended' => '/login-conta/minha-conta',
            ]));
    }

    public function test_customer_can_update_profile_and_manage_addresses(): void
    {
        [$tenant, $customer, $user] = $this->tenantCustomerAndUser('enderecos-conta');

        $this->actingAs($user)
            ->patch('/enderecos-conta/minha-conta/perfil', [
                'name' => 'Cliente Atualizado',
                'email' => 'atualizado@example.com',
                'cpf' => '111.222.333-44',
                'phone' => '(47) 99999-1111',
            ])
            ->assertRedirect(route('public.account.index', $tenant->slug));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Cliente Atualizado',
            'email' => 'atualizado@example.com',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Cliente Atualizado',
            'cpf' => '111.222.333-44',
        ]);

        $this->actingAs($user)
            ->post('/enderecos-conta/minha-conta/enderecos', [
                'label' => 'Trabalho',
                'recipient_name' => 'Portaria',
                'phone' => '(47) 98888-0000',
                'postal_code' => '89000-000',
                'street' => 'Rua Comercial',
                'number' => '55',
                'complement' => 'Sala 10',
                'neighborhood' => 'Centro',
                'city' => 'Blumenau',
                'state' => 'SC',
                'is_default' => '1',
            ])
            ->assertRedirect(route('public.account.index', $tenant->slug));

        $address = CustomerAddress::firstOrFail();

        $this->assertSame($tenant->id, $address->tenant_id);
        $this->assertTrue($address->is_default);

        $this->actingAs($user)
            ->patch('/enderecos-conta/minha-conta/enderecos/'.$address->id, [
                'label' => 'Casa',
                'street' => 'Rua Residencial',
                'number' => '99',
                'city' => 'Gaspar',
                'state' => 'SC',
                'is_default' => '1',
            ])
            ->assertRedirect(route('public.account.index', $tenant->slug));

        $this->assertDatabaseHas('customer_addresses', [
            'id' => $address->id,
            'label' => 'Casa',
            'street' => 'Rua Residencial',
        ]);

        $this->actingAs($user)
            ->delete('/enderecos-conta/minha-conta/enderecos/'.$address->id)
            ->assertRedirect(route('public.account.index', $tenant->slug));

        $this->assertDatabaseMissing('customer_addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_customer_with_tenant_id_sees_only_the_public_customer_menu(): void
    {
        [$tenant, $customer, $user] = $this->tenantCustomerAndUser('menu-cliente');
        $user->update(['tenant_id' => $tenant->id]);

        $this->actingAs($user)
            ->get('/menu-cliente/minha-conta')
            ->assertOk()
            ->assertSee('Loja')
            ->assertSee('href="'.route('public.store.index', $tenant->slug).'"', false)
            ->assertDontSee('/?'.$tenant->slug)
            ->assertSee('Minha conta')
            ->assertSee('Novo chamado')
            ->assertSee('Acompanhamento')
            ->assertSee('Carrinho')
            ->assertSee('Cliente')
            ->assertDontSee('Kanban')
            ->assertDontSee('Estoque')
            ->assertDontSee('Agenda')
            ->assertDontSee('Customização')
            ->assertDontSee('Microssite');
    }

    public function test_checkout_can_attach_delivery_address_to_customer_order(): void
    {
        [$tenant, $customer, $user] = $this->tenantCustomerAndUser('checkout-endereco');
        $item = Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Fonte 65W',
            'type' => 'product',
            'is_for_sale' => true,
            'cost_price' => 80,
            'sale_price' => 160,
            'stock_quantity' => 5,
            'min_stock_alert' => 1,
        ]);
        $address = CustomerAddress::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'label' => 'Casa',
            'street' => 'Rua Entrega',
            'number' => '321',
            'city' => 'Blumenau',
            'state' => 'SC',
        ]);

        $this->actingAs($user)
            ->post('/checkout-endereco/checkout/carrinho', [
                'item_id' => $item->id,
                'quantity' => 1,
            ]);

        $this->actingAs($user)
            ->post('/checkout-endereco/checkout', [
                'payment_method' => 'pix',
                'fulfillment_method' => 'delivery',
                'customer_address_id' => $address->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('checkout_orders', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'customer_address_id' => $address->id,
            'fulfillment_method' => 'delivery',
            'fulfillment_status' => 'pending',
        ]);
    }

    private function tenantCustomerAndUser(string $slug, ?User $user = null): array
    {
        $tenant = $this->tenant($slug);
        $user ??= User::create([
            'name' => 'Cliente Conta',
            'email' => $slug.'@example.com',
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

        return [$tenant, $customer, $user];
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

    private function serviceOrder(Tenant $tenant, Customer $customer, string $deviceModel, string $columnName): ServiceOrder
    {
        $column = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => $columnName,
            'order_index' => 1,
        ]);

        return ServiceOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'device_model' => $deviceModel,
            'defect_symptoms' => 'Sintoma teste.',
            'status' => 'pending',
            'total_cost' => 0,
            'total_price' => 0,
        ]);
    }
}
