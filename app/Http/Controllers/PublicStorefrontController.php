<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicStorefrontController extends Controller
{
    public function index(Request $request, $slug)
    {
        $tenant = Tenant::with('customization')
            ->where('slug', $slug)
            ->firstOrFail();

        $tenant->enforceSubscriptionLifecycle();

        abort_unless($tenant->isPubliclyAvailable(), 404);

        $request->session()->put('public_tenant_id', $tenant->id);
        $request->session()->put('public_tenant_slug', $tenant->slug);

        $items = $tenant->hasFeature('catalog')
            ? $tenant->items()
                ->publicCatalog()
                ->orderBy('name')
                ->paginate(12)
            : new LengthAwarePaginator([], 0, 12);

        return view('public.storefront', compact('tenant', 'items'));
    }
}
