<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\Tenant;
use Illuminate\Http\Request;

class PublicTrackingController extends Controller
{
    public function index(Request $request, $slug)
    {
        $tenant = $this->publicTenant($slug);
        $this->rememberTenantContext($request, $tenant);

        if (! $request->user()) {
            return redirect()->route('public.customer.login', [
                'slug' => $tenant->slug,
                'intended' => '/'.$tenant->slug.'/acompanhamento',
            ]);
        }

        abort_unless($request->user()->isCustomer(), 403);

        $authenticatedOrders = $this->ordersForAuthenticatedCustomer($tenant)->get();

        return view('public.tracking.index', compact('tenant', 'authenticatedOrders'));
    }

    public function show(Request $request, $slug)
    {
        $tenant = $this->publicTenant($slug);
        $this->rememberTenantContext($request, $tenant);

        if (! $request->user()) {
            return redirect()->route('public.customer.login', [
                'slug' => $tenant->slug,
                'intended' => '/'.$tenant->slug.'/acompanhamento',
            ]);
        }

        abort_unless($request->user()->isCustomer(), 403);

        $orders = $this->ordersForAuthenticatedCustomer($tenant)->get();

        return view('public.tracking.show', [
            'tenant' => $tenant,
            'orders' => $orders,
            'lookup' => null,
        ]);
    }

    public function updateBudgetStatus(Request $request, $slug, $os_id)
    {
        $tenant = $this->publicTenant($slug);
        $this->rememberTenantContext($request, $tenant);
        abort_unless($request->user()?->isCustomer(), 403);

        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
        ]);

        $serviceOrder = $this->ordersForAuthenticatedCustomer($tenant)
            ->where('id', $os_id)
            ->firstOrFail();

        abort_unless((float) $serviceOrder->total_price > 0, 404);
        abort_if(in_array($serviceOrder->status, ['finished', 'rejected'], true), 409);

        $serviceOrder->update([
            'status' => $validated['decision'],
            'budget_decided_at' => now(),
        ]);

        return redirect()
            ->route('public.tracking.index', $tenant->slug)
            ->with('success', $validated['decision'] === 'approved'
                ? 'Orçamento aprovado. A assistência foi sinalizada para prosseguir.'
                : 'Orçamento recusado. A assistência foi sinalizada.');
    }

    public function storeAttachment(Request $request, string $slug, int $os_id)
    {
        $tenant = $this->publicTenant($slug);
        $this->rememberTenantContext($request, $tenant);
        abort_unless($request->user()?->isCustomer(), 403);

        $validated = $request->validate([
            'attachments' => ['required', 'array', 'max:6'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi', 'max:20480'],
        ]);

        $serviceOrder = $this->ordersForAuthenticatedCustomer($tenant)
            ->where('id', $os_id)
            ->firstOrFail();

        $attachments = collect($request->file('attachments', []))
            ->map(fn ($file) => $file->store('service-orders/'.$tenant->id, 'public'))
            ->values()
            ->all();

        $serviceOrder->update([
            'attachments' => array_values(array_merge($serviceOrder->attachments ?? [], $attachments)),
        ]);

        return redirect()
            ->route('public.tracking.index', $tenant->slug)
            ->with('success', 'Anexos adicionados ao chamado #'.$serviceOrder->id.'.');
    }

    private function publicTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        $tenant->enforceSubscriptionLifecycle();

        abort_unless($tenant->isPubliclyAvailable(), 404);

        return $tenant;
    }

    private function ordersForAuthenticatedCustomer(Tenant $tenant)
    {
        $user = auth()->user();

        return ServiceOrder::with(['customer', 'kanbanColumn'])
            ->where('tenant_id', $tenant->id)
            ->when(! $user?->isCustomer(), fn ($query) => $query->whereRaw('1 = 0'))
            ->whereHas('customer', fn ($query) => $query->where('user_id', $user?->id))
            ->latest();
    }

    private function rememberTenantContext(Request $request, Tenant $tenant): void
    {
        $request->session()->put('public_tenant_id', $tenant->id);
        $request->session()->put('public_tenant_slug', $tenant->slug);
    }
}
