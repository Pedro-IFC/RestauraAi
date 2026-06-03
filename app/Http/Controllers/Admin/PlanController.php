<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::withCount('tenants')
            ->latest()
            ->paginate(10);

        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        $plan = new Plan([
            'trial_days_allowed' => 0,
            'features' => $this->defaultFeatures(),
        ]);

        return view('admin.plans.create', compact('plan'));
    }

    public function store(Request $request)
    {
        Plan::create($this->validatedData($request));

        return redirect()
            ->route('planos.index')
            ->with('success', 'Plano criado com sucesso.');
    }

    public function show(Plan $plano)
    {
        return redirect()->route('planos.edit', $plano);
    }

    public function edit(Plan $plano)
    {
        $plan = $plano;
        $plan->features = array_replace_recursive($this->defaultFeatures(), $plan->features ?? []);

        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plano)
    {
        $plano->update($this->validatedData($request, $plano));

        return redirect()
            ->route('planos.index')
            ->with('success', 'Plano atualizado com sucesso.');
    }

    public function destroy(Plan $plano)
    {
        if ($plano->tenants()->exists()) {
            return back()->with('error', 'Este plano possui assistências vinculadas e não pode ser excluído.');
        }

        $plano->delete();

        return redirect()
            ->route('planos.index')
            ->with('success', 'Plano excluído com sucesso.');
    }

    private function validatedData(Request $request, ?Plan $plan = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('plans', 'name')->ignore($plan?->id),
            ],
            'price_monthly' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'price_yearly' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'trial_days_allowed' => ['required', 'integer', 'min:0', 'max:365'],
            'features.max_service_orders_per_month' => ['nullable', 'integer', 'min:0'],
            'features.max_users' => ['nullable', 'integer', 'min:0'],
            'features.max_items' => ['nullable', 'integer', 'min:0'],
            'features.max_public_products' => ['nullable', 'integer', 'min:0'],
            'features.kanban' => ['nullable', 'boolean'],
            'features.catalog' => ['nullable', 'boolean'],
            'features.dashboard' => ['nullable', 'boolean'],
            'features.customization' => ['nullable', 'boolean'],
            'features.custom_domain' => ['nullable', 'boolean'],
            'features.priority_support' => ['nullable', 'boolean'],
        ]);

        $features = array_replace($this->defaultFeatures(), $validated['features'] ?? []);

        foreach ($features as $key => $value) {
            if (str_starts_with($key, 'max_')) {
                $features[$key] = $value === null || $value === '' ? null : (int) $value;
                continue;
            }

            $features[$key] = (bool) $request->input("features.$key", false);
        }

        return [
            'name' => $validated['name'],
            'price_monthly' => $validated['price_monthly'],
            'price_yearly' => $validated['price_yearly'] ?? null,
            'trial_days_allowed' => $validated['trial_days_allowed'],
            'features' => $features,
        ];
    }

    private function defaultFeatures(): array
    {
        return [
            'max_service_orders_per_month' => null,
            'max_users' => null,
            'max_items' => null,
            'max_public_products' => null,
            'kanban' => false,
            'catalog' => false,
            'dashboard' => false,
            'customization' => false,
            'custom_domain' => false,
            'priority_support' => false,
        ];
    }
}
