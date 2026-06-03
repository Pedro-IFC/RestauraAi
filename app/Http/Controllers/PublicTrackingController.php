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

        return view('public.tracking.index', compact('tenant'));
    }

    public function show(Request $request, $slug)
    {
        $tenant = $this->publicTenant($slug);
        $validated = $request->validate([
            'lookup' => 'required|string|max:30',
        ]);

        $orders = $this->ordersForLookup($tenant, $validated['lookup'])->get();

        if ($orders->isEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['lookup' => 'Nenhum chamado encontrado para estes dados.']);
        }

        return view('public.tracking.show', [
            'tenant' => $tenant,
            'orders' => $orders,
            'lookup' => $validated['lookup'],
        ]);
    }

    public function updateBudgetStatus(Request $request, $slug, $os_id)
    {
        $tenant = $this->publicTenant($slug);
        $validated = $request->validate([
            'lookup' => 'required|string|max:30',
            'decision' => 'required|in:approved,rejected',
        ]);

        $serviceOrder = $this->ordersForLookup($tenant, $validated['lookup'])
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

    private function publicTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        $tenant->enforceSubscriptionLifecycle();

        abort_unless($tenant->isPubliclyAvailable(), 404);

        return $tenant;
    }

    private function ordersForLookup(Tenant $tenant, string $lookup)
    {
        $digits = preg_replace('/\D+/', '', $lookup);

        return ServiceOrder::with(['customer', 'kanbanColumn'])
            ->where('tenant_id', $tenant->id)
            ->where(function ($query) use ($lookup, $digits) {
                if ($digits !== '') {
                    $query->orWhere('id', (int) $digits);
                }

                $query->orWhereHas('customer', function ($customerQuery) use ($lookup, $digits) {
                    $customerQuery->where('cpf', $lookup);

                    if ($digits !== '') {
                        $customerQuery->orWhereRaw(
                            "REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?",
                            [$digits]
                        );
                    }
                });
            })
            ->latest();
    }
}
