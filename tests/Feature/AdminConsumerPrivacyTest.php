<?php

namespace Tests\Feature;

use App\Models\CheckoutOrder;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\KanbanColumn;
use App\Models\MagicLoginCode;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminConsumerPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_list_and_view_consumer_lgpd_dossier(): void
    {
        $superadmin = $this->superadmin();
        [$consumer, $tenant, $customer] = $this->consumerFixture('lgpd-lista@example.com');
        $this->serviceOrder($tenant, $customer, 'Notebook LGPD');
        $this->checkoutOrder($tenant, $customer);

        $this->actingAs($superadmin)
            ->get(route('admin.consumers.index'))
            ->assertOk()
            ->assertSee('Consumidores')
            ->assertSee('lgpd-lista@example.com');

        $this->actingAs($superadmin)
            ->get(route('admin.consumers.show', $consumer))
            ->assertOk()
            ->assertSee('Notebook LGPD')
            ->assertSee('Direito ao esquecimento')
            ->assertSee('Exportar JSON LGPD');
    }

    public function test_superadmin_can_export_consumer_data_as_json(): void
    {
        $superadmin = $this->superadmin();
        [$consumer, $tenant, $customer] = $this->consumerFixture('lgpd-export@example.com');
        $this->serviceOrder($tenant, $customer, 'Celular Export');
        $this->checkoutOrder($tenant, $customer);

        $response = $this->actingAs($superadmin)
            ->get(route('admin.consumers.export', $consumer))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="consumidor-'.$consumer->id.'-lgpd.json"');

        $payload = $response->json();

        $this->assertSame('lgpd-export@example.com', $payload['consumer']['email']);
        $this->assertSame(1, $payload['analytics']['service_orders']);
        $this->assertSame(1, $payload['analytics']['checkout_orders']);
        $this->assertSame('Celular Export', $payload['customers'][0]['service_orders'][0]['device_model']);
    }

    public function test_superadmin_can_delete_customer_account_and_anonymize_personal_data(): void
    {
        $superadmin = $this->superadmin();
        [$consumer, $tenant, $customer] = $this->consumerFixture('lgpd-delete@example.com');
        $serviceOrder = $this->serviceOrder($tenant, $customer, 'Tablet Delete');
        $order = $this->checkoutOrder($tenant, $customer);

        CustomerAddress::create([
            'user_id' => $consumer->id,
            'tenant_id' => $tenant->id,
            'label' => 'Casa',
            'street' => 'Rua Privada',
            'number' => '10',
            'city' => 'Blumenau',
            'state' => 'SC',
        ]);
        MagicLoginCode::create([
            'tenant_id' => $tenant->id,
            'email' => 'lgpd-delete@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);
        DB::table('sessions')->insert([
            'id' => 'consumer-session',
            'user_id' => $consumer->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => 'payload',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($superadmin)
            ->delete(route('admin.consumers.destroy', $consumer), [
                'confirm' => '1',
            ])
            ->assertRedirect(route('admin.consumers.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $consumer->id,
        ]);
        $this->assertDatabaseMissing('customer_addresses', [
            'user_id' => $consumer->id,
        ]);
        $this->assertDatabaseMissing('magic_login_codes', [
            'email' => 'lgpd-delete@example.com',
        ]);
        $this->assertDatabaseMissing('sessions', [
            'id' => 'consumer-session',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'user_id' => null,
            'name' => 'Consumidor removido #'.$consumer->id,
            'cpf' => null,
            'phone' => null,
            'email' => null,
        ]);
        $this->assertDatabaseHas('service_orders', [
            'id' => $serviceOrder->id,
            'customer_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('checkout_orders', [
            'id' => $order->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_consumer_privacy_routes_reject_non_customer_users(): void
    {
        $superadmin = $this->superadmin();
        $tenantUser = User::create([
            'name' => 'Tecnico',
            'email' => 'tecnico-lgpd@example.com',
            'password' => 'password',
            'role' => 'technician',
            'tenant_id' => null,
        ]);

        $this->actingAs($superadmin)
            ->get(route('admin.consumers.show', $tenantUser))
            ->assertNotFound();
    }

    private function superadmin(): User
    {
        return User::create([
            'name' => 'Superadmin LGPD',
            'email' => 'superadmin-lgpd@example.com',
            'password' => 'password',
            'role' => 'superadmin',
            'tenant_id' => null,
        ]);
    }

    private function consumerFixture(string $email): array
    {
        $plan = Plan::create([
            'name' => 'Plano LGPD '.$email,
            'price_monthly' => 99,
            'features' => Plan::presetFeatures('ouro'),
            'trial_days_allowed' => 7,
        ]);
        $tenant = Tenant::create([
            'plan_id' => $plan->id,
            'name' => 'Assistencia LGPD',
            'slug' => 'lgpd-'.substr(md5($email), 0, 8),
            'document' => substr(md5('doc'.$email), 0, 14),
            'status' => 'active',
        ]);
        $consumer = User::create([
            'name' => 'Consumidor LGPD',
            'email' => $email,
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'tenant_id' => null,
        ]);
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'user_id' => $consumer->id,
            'name' => 'Consumidor LGPD',
            'cpf' => '123.456.789-00',
            'phone' => '(47) 99999-0000',
            'email' => $email,
        ]);

        return [$consumer, $tenant, $customer];
    }

    private function serviceOrder(Tenant $tenant, Customer $customer, string $deviceModel): ServiceOrder
    {
        $column = KanbanColumn::create([
            'tenant_id' => $tenant->id,
            'name' => 'Triagem',
            'order_index' => 1,
        ]);

        return ServiceOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'device_model' => $deviceModel,
            'defect_symptoms' => 'Sintoma LGPD.',
            'status' => 'pending',
            'total_cost' => 0,
            'total_price' => 0,
        ]);
    }

    private function checkoutOrder(Tenant $tenant, Customer $customer): CheckoutOrder
    {
        $order = CheckoutOrder::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'type' => 'products',
            'status' => 'open',
            'payment_method' => 'pix',
            'fulfillment_method' => 'pickup',
            'fulfillment_status' => 'pending',
            'total_amount' => 120,
            'fixgo_commission_rate' => 10,
            'fixgo_commission_amount' => 12,
            'tenant_amount' => 108,
        ]);

        $order->items()->create([
            'description' => 'Produto LGPD',
            'quantity' => 1,
            'unit_price' => 120,
            'total_price' => 120,
        ]);

        return $order;
    }
}
