<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantCustomization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomizationController extends Controller
{
    public function edit()
    {
        $tenant = Auth::user()->tenant;
        $customization = TenantCustomization::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'primary_color' => '#facc15',
                'secondary_color' => '#fffbeb',
            ]
        );

        $canUseAdvancedCustomization = $tenant->hasFeature('customization_advanced');

        return view('tenant.customization.edit', compact('canUseAdvancedCustomization', 'customization'));
    }

    public function update(Request $request)
    {
        $tenant = Auth::user()->tenant;
        $customization = TenantCustomization::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'primary_color' => '#facc15',
                'secondary_color' => '#fffbeb',
            ]
        );

        $canUseAdvancedCustomization = $tenant->hasFeature('customization_advanced');

        $validated = $request->validate([
            'logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'banners' => 'nullable|array|max:5',
            'banners.*' => 'image|max:4096',
            'remove_banners' => 'nullable|array',
            'remove_banners.*' => 'string',
            'about_text' => 'nullable|string|max:5000',
            'instagram_handle' => 'nullable|string|max:255',
            'address_text' => 'nullable|string|max:1000',
            'google_maps_iframe' => 'nullable|string|max:5000',
        ]);

        $logo = $customization->logo;
        if ($canUseAdvancedCustomization && $request->boolean('remove_logo') && $logo) {
            Storage::disk('public')->delete($logo);
            $logo = null;
        }

        if ($canUseAdvancedCustomization && $request->hasFile('logo')) {
            if ($logo) {
                Storage::disk('public')->delete($logo);
            }

            $logo = $request->file('logo')->store('tenant-customizations/'.Auth::user()->tenant_id.'/logo', 'public');
        }

        $existingBanners = collect($customization->banners ?? []);
        $bannersToRemove = $existingBanners
            ->intersect($validated['remove_banners'] ?? [])
            ->values();

        $banners = $existingBanners
            ->reject(fn (string $path) => $bannersToRemove->contains($path))
            ->values();

        if ($canUseAdvancedCustomization) {
            foreach ($bannersToRemove as $path) {
                Storage::disk('public')->delete($path);
            }
        } else {
            $banners = $existingBanners;
        }

        if ($canUseAdvancedCustomization) {
            foreach ($request->file('banners', []) as $banner) {
                $banners->push($banner->store('tenant-customizations/'.Auth::user()->tenant_id.'/banners', 'public'));
            }
        }

        $customization->update([
            'logo' => $logo,
            'primary_color' => $canUseAdvancedCustomization ? $validated['primary_color'] : $customization->primary_color,
            'secondary_color' => $canUseAdvancedCustomization ? $validated['secondary_color'] : $customization->secondary_color,
            'banners' => $banners->take(5)->values()->all(),
            'about_text' => $validated['about_text'] ?? null,
            'instagram_handle' => $canUseAdvancedCustomization ? $this->normalizeInstagram($validated['instagram_handle'] ?? null) : $customization->instagram_handle,
            'address_text' => $validated['address_text'] ?? null,
            'google_maps_iframe' => $canUseAdvancedCustomization ? ($validated['google_maps_iframe'] ?? null) : $customization->google_maps_iframe,
        ]);

        return redirect()
            ->route('tenant.customization.edit')
            ->with('success', 'Layout do microssite atualizado.');
    }

    private function normalizeInstagram(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);

        if (preg_match('~instagram\.com/([^/?#]+)~i', $value, $matches)) {
            return '@'.ltrim($matches[1], '@');
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return '@'.ltrim($value, '@');
    }
}
