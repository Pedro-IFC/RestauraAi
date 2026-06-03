<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\KanbanColumn;
use App\Models\Plan;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicServiceOrderPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_service_order_with_media_attachments(): void
    {
        Storage::fake('public');

        $tenant = $this->tenant('rf03-assistencia');

        $this->post('/rf03-assistencia/chamados', [
            'name' => 'Cliente Portal',
            'cpf' => '123.456.789-00',
            'phone' => '(47) 99999-0000',
            'email' => 'cliente@example.com',
            'device_model' => 'iPhone 14',
            'defect_symptoms' => 'Tela piscando depois de queda.',
            'attachments' => [
                UploadedFile::fake()->create('defeito.jpg', 100, 'image/jpeg'),
            ],
        ])->assertRedirect('/rf03-assistencia');

        $serviceOrder = ServiceOrder::with('customer')->firstOrFail();

        $this->assertSame($tenant->id, $serviceOrder->tenant_id);
        $this->assertSame('Cliente Portal', $serviceOrder->customer->name);
        $this->assertSame('iPhone 14', $serviceOrder->device_model);
        $this->assertSame('pending', $serviceOrder->status);
        $this->assertCount(1, $serviceOrder->attachments);
        Storage::disk('public')->assertExists($serviceOrder->attachments[0]);
    }

    public function test_customer_can_track_order_by_cpf_and_approve_budget(): void
    {
        $tenant = $this->tenant('rf04-assistencia');
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cliente Acompanhamento',
            'cpf' => '987.654.321-00',
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
            'device_model' => 'Notebook Dell',
            'defect_symptoms' => 'Não liga.',
            'status' => 'budgeting',
            'total_cost' => 120,
            'total_price' => 350,
        ]);

        $this->post('/rf04-assistencia/acompanhamento/buscar', [
            'lookup' => '98765432100',
        ])
            ->assertOk()
            ->assertSee('Chamado #'.$serviceOrder->id)
            ->assertSee('Na Bancada')
            ->assertSee('R$ 350,00')
            ->assertSee('Aprovar orçamento')
            ->assertSee('Recusar orçamento');

        $this->patch('/rf04-assistencia/acompanhamento/'.$serviceOrder->id.'/orcamento', [
            'lookup' => '98765432100',
            'decision' => 'approved',
        ])->assertRedirect(route('public.tracking.index', $tenant->slug));

        $serviceOrder->refresh();

        $this->assertSame('approved', $serviceOrder->status);
        $this->assertNotNull($serviceOrder->budget_decided_at);
    }

    public function test_tracking_is_limited_to_the_tenant_slug(): void
    {
        $tenant = $this->tenant('assistencia-certa');
        $otherTenant = $this->tenant('assistencia-errada');
        $customer = Customer::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Cliente Outra Assistência',
            'cpf' => '111.222.333-44',
        ]);
        $column = KanbanColumn::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Testes',
            'order_index' => 1,
        ]);

        ServiceOrder::create([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $customer->id,
            'kanban_column_id' => $column->id,
            'device_model' => 'Aparelho de outro tenant',
            'defect_symptoms' => 'Não deve aparecer.',
            'status' => 'budgeting',
            'total_cost' => 10,
            'total_price' => 90,
        ]);

        $this->post('/assistencia-certa/acompanhamento/buscar', [
            'lookup' => '11122233344',
        ])
            ->assertSessionHasErrors('lookup')
            ->assertDontSee('Aparelho de outro tenant');

        $this->assertSame($tenant->slug, 'assistencia-certa');
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
}
