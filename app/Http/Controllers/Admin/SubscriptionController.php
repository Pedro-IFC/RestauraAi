<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function suspend(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'subscription_notes' => $request->input('subscription_notes', $tenant->subscription_notes),
        ]);

        return back()->with('success', 'Assistência suspensa por inadimplência.');
    }

    public function reactivate($id)
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->update([
            'status' => 'active',
            'payment_overdue_since' => null,
            'suspended_at' => null,
            'canceled_at' => null,
        ]);

        return back()->with('success', 'Assistência reativada.');
    }

    public function cancel(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $tenant->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'subscription_notes' => $request->input('subscription_notes', $tenant->subscription_notes),
        ]);

        return back()->with('success', 'Assinatura cancelada.');
    }

    public function updateBilling(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:active,trial,suspended,canceled',
            'payment_overdue_since' => 'nullable|date',
            'payment_grace_days' => 'required|integer|min:0|max:90',
            'subscription_notes' => 'nullable|string|max:2000',
        ]);

        $tenant->update([
            'status' => $validated['status'],
            'payment_overdue_since' => $validated['payment_overdue_since'] ?? null,
            'payment_grace_days' => $validated['payment_grace_days'],
            'suspended_at' => $validated['status'] === 'suspended' ? ($tenant->suspended_at ?? now()) : null,
            'canceled_at' => $validated['status'] === 'canceled' ? ($tenant->canceled_at ?? now()) : null,
            'subscription_notes' => $validated['subscription_notes'] ?? null,
        ]);

        $tenant->enforceSubscriptionLifecycle();

        return back()->with('success', 'Dados de cobrança atualizados.');
    }

    public function updateTrialDays(Request $request, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $validated = $request->validate([
            'trial_days' => 'required|integer|min:0|max:365',
        ]);

        $tenant->update([
            'status' => 'trial',
            'trial_ends_at' => now()->addDays($validated['trial_days'])->endOfDay(),
        ]);

        return back()->with('success', 'Período de testes atualizado.');
    }
}
