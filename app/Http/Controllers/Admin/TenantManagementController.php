<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Tenant::with(['plan', 'users'])
            ->withCount(['serviceOrders', 'items', 'users']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('document', 'like', '%'.$search.'%');
            });
        }

        $tenants = $query
            ->orderByRaw("CASE status WHEN 'suspended' THEN 0 WHEN 'trial' THEN 1 WHEN 'active' THEN 2 WHEN 'canceled' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $plans = Plan::orderBy('name')->get();

        return view('admin.tenants.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
        ]);

        $validated = $request->validate([
            'plan_id' => ['required', Rule::exists('plans', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'document' => ['required', 'string', 'max:30', Rule::unique('tenants', 'document')],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'owner_password' => ['required', 'string', 'min:6', 'max:255'],
        ]);

        $tenant = DB::transaction(function () use ($validated) {
            $plan = Plan::findOrFail($validated['plan_id']);
            $trialDays = (int) $plan->trial_days_allowed;

            $tenant = Tenant::create([
                'plan_id' => $plan->id,
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'document' => $validated['document'],
                'status' => $trialDays > 0 ? 'trial' : 'active',
                'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays)->endOfDay() : null,
                'payment_grace_days' => 7,
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'role' => 'admin',
            ]);

            return $tenant;
        });

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Assistência criada com trial aplicado conforme o plano.');
    }

    public function show($id)
    {
        $tenant = Tenant::with(['plan', 'users', 'customization'])
            ->withCount(['serviceOrders', 'items', 'users'])
            ->findOrFail($id);

        return view('admin.tenants.show', compact('tenant'));
    }
}
