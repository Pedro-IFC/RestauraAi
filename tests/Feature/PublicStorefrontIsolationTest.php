<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantCustomization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStorefrontIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_slug_loads_only_requested_tenant_identity_and_catalog(): void
    {
        $tenant = $this->tenant('ph-informatica', 'PH Informática', 'ouro');
        $otherTenant = $this->tenant('outra-assistencia', 'Outra Assistência', 'ouro');

        TenantCustomization::create([
            'tenant_id' => $tenant->id,
            'primary_color' => '#123456',
            'secondary_color' => '#fedcba',
            'about_text' => 'Especialistas em notebooks gamer.',
            'instagram_handle' => '@phinformatica',
            'address_text' => 'Rua da Assistência, 123',
        ]);

        TenantCustomization::create([
            'tenant_id' => $otherTenant->id,
            'primary_color' => '#654321',
            'secondary_color' => '#abcdef',
            'about_text' => 'Texto privado da outra assistência.',
            'address_text' => 'Endereço da concorrente',
        ]);

        $this->item($tenant, 'Tela LCD 15.6', 'product', true, 3);
        $this->item($tenant, 'Cabo USB oculto', 'product', false, 5);
        $this->item($tenant, 'Pasta térmica bancada', 'supply', false, 20);
        $this->item($tenant, 'Carregador sem estoque', 'product', true, 0);
        $this->item($otherTenant, 'Produto de outra assistência', 'product', true, 9);

        $this->get('/ph-informatica')
            ->assertOk()
            ->assertSee('PH Informática')
            ->assertSee('Especialistas em notebooks gamer.')
            ->assertSee('Rua da Assistência, 123')
            ->assertSee('#123456')
            ->assertSee('#fedcba')
            ->assertSee('Tela LCD 15.6')
            ->assertDontSee('Outra Assistência')
            ->assertDontSee('Texto privado da outra assistência.')
            ->assertDontSee('Endereço da concorrente')
            ->assertDontSee('Produto de outra assistência')
            ->assertDontSee('Cabo USB oculto')
            ->assertDontSee('Pasta térmica bancada')
            ->assertDontSee('Carregador sem estoque');
    }

    public function test_public_catalog_does_not_render_items_when_plan_has_no_catalog_feature(): void
    {
        $tenant = $this->tenant('bronze-assistencia', 'Bronze Assistência', 'bronze');

        $this->item($tenant, 'Produto marcado para venda', 'product', true, 4);

        $this->get('/bronze-assistencia')
            ->assertOk()
            ->assertSee('Bronze Assistência')
            ->assertSee('Nenhum produto disponível no momento.')
            ->assertDontSee('Produto marcado para venda');
    }

    public function test_unknown_public_slug_returns_not_found(): void
    {
        $this->get('/assistencia-inexistente')
            ->assertNotFound();
    }

    public function test_public_checkout_rejects_item_from_another_tenant(): void
    {
        $tenant = $this->tenant('loja-certa', 'Loja Certa', 'ouro');
        $otherTenant = $this->tenant('loja-errada', 'Loja Errada', 'ouro');
        $otherItem = $this->item($otherTenant, 'Peça de outro estoque', 'product', true, 2);

        $this->post('/loja-certa/checkout/carrinho', [
            'item_id' => $otherItem->id,
            'quantity' => 1,
        ])->assertNotFound();

        $this->assertDatabaseHas('items', [
            'id' => $otherItem->id,
            'stock_quantity' => 2,
        ]);
    }

    private function tenant(string $slug, string $name, string $planPreset): Tenant
    {
        $plan = Plan::create([
            'name' => 'Plano '.$name,
            'price_monthly' => 99,
            'features' => Plan::presetFeatures($planPreset),
            'trial_days_allowed' => 7,
        ]);

        return Tenant::create([
            'plan_id' => $plan->id,
            'name' => $name,
            'slug' => $slug,
            'document' => substr(md5($slug), 0, 14),
            'status' => 'active',
        ]);
    }

    private function item(Tenant $tenant, string $name, string $type, bool $isForSale, int $stockQuantity): Item
    {
        return Item::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'type' => $type,
            'is_for_sale' => $isForSale,
            'cost_price' => 50,
            'sale_price' => 120,
            'stock_quantity' => $stockQuantity,
            'min_stock_alert' => 1,
        ]);
    }
}
