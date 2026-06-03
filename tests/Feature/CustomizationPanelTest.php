<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantCustomization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomizationPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_microsite_customization_with_uploads(): void
    {
        Storage::fake('public');

        [$tenant, $user] = $this->tenantAndUser();

        $this->actingAs($user)
            ->put(route('tenant.customization.update'), [
                'logo' => UploadedFile::fake()->create('logo.png', 128, 'image/png'),
                'primary_color' => '#123456',
                'secondary_color' => '#abcdef',
                'banners' => [
                    UploadedFile::fake()->create('banner-1.jpg', 256, 'image/jpeg'),
                    UploadedFile::fake()->create('banner-2.jpg', 256, 'image/jpeg'),
                ],
                'about_text' => "Assistência especializada\nAtendimento com garantia.",
                'instagram_handle' => 'https://instagram.com/restauraai',
                'address_text' => 'Rua Central, 123',
                'google_maps_iframe' => '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>',
            ])
            ->assertRedirect(route('tenant.customization.edit'));

        $customization = TenantCustomization::where('tenant_id', $tenant->id)->firstOrFail();

        $this->assertSame('#123456', $customization->primary_color);
        $this->assertSame('#abcdef', $customization->secondary_color);
        $this->assertSame('@restauraai', $customization->instagram_handle);
        $this->assertSame('https://www.google.com/maps/embed?pb=test', $customization->google_maps_embed_src);
        $this->assertCount(2, $customization->banners);

        Storage::disk('public')->assertExists($customization->logo);
        Storage::disk('public')->assertExists($customization->banners[0]);
        Storage::disk('public')->assertExists($customization->banners[1]);
    }

    public function test_public_storefront_uses_customized_identity(): void
    {
        [$tenant] = $this->tenantAndUser();

        TenantCustomization::create([
            'tenant_id' => $tenant->id,
            'logo' => 'tenant-customizations/logo.png',
            'primary_color' => '#123456',
            'secondary_color' => '#abcdef',
            'banners' => ['tenant-customizations/banner.jpg'],
            'about_text' => 'Especialistas em notebooks.',
            'instagram_handle' => '@restauraai',
            'address_text' => 'Rua Central, 123',
            'google_maps_iframe' => '<iframe src="https://www.google.com/maps/embed?pb=test"></iframe>',
        ]);

        Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cabo USB-C',
            'type' => 'product',
            'is_for_sale' => true,
            'cost_price' => 10,
            'sale_price' => 35,
            'stock_quantity' => 5,
            'min_stock_alert' => 1,
        ]);

        $this->get('/'.$tenant->slug)
            ->assertOk()
            ->assertSee('Especialistas em notebooks.')
            ->assertSee('Rua Central, 123')
            ->assertSee('https://instagram.com/restauraai')
            ->assertSee('https://www.google.com/maps/embed?pb=test')
            ->assertSee('Cabo USB-C');
    }

    public function test_customization_update_is_scoped_to_authenticated_tenant(): void
    {
        [$tenant, $user] = $this->tenantAndUser('tenant-a', 'tenant-a@example.com');
        [$otherTenant] = $this->tenantAndUser('tenant-b', 'tenant-b@example.com');

        TenantCustomization::create([
            'tenant_id' => $otherTenant->id,
            'primary_color' => '#000000',
            'secondary_color' => '#ffffff',
            'about_text' => 'Outro tenant',
        ]);

        $this->actingAs($user)
            ->put(route('tenant.customization.update'), [
                'primary_color' => '#111111',
                'secondary_color' => '#eeeeee',
                'about_text' => 'Tenant correto',
            ])
            ->assertRedirect(route('tenant.customization.edit'));

        $this->assertDatabaseHas('tenant_customizations', [
            'tenant_id' => $tenant->id,
            'about_text' => 'Tenant correto',
        ]);
        $this->assertDatabaseHas('tenant_customizations', [
            'tenant_id' => $otherTenant->id,
            'about_text' => 'Outro tenant',
        ]);
    }

    private function tenantAndUser(string $slug = 'customizacao-teste', string $email = 'customizacao@example.com'): array
    {
        $plan = Plan::create([
            'name' => 'Plano '.$slug,
            'price_monthly' => 99,
            'features' => array_replace(Plan::presetFeatures('ouro'), [
                'catalog' => true,
            ]),
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
            'name' => 'Admin Customizacao',
            'email' => $email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        return [$tenant, $user];
    }
}
