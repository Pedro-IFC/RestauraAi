<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Tenant;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PublicCustomerAccountController extends Controller
{
    public function index(Request $request, string $slug)
    {
        [$tenant, $customer] = $this->tenantAndCustomer($request, $slug);

        $serviceOrders = $customer->serviceOrders()
            ->with(['tenant', 'kanbanColumn'])
            ->latest()
            ->get();

        $equipment = $serviceOrders
            ->groupBy(fn ($order) => mb_strtolower($order->device_model))
            ->map(function ($orders) {
                $latest = $orders->sortByDesc('created_at')->first();

                return [
                    'device_model' => $latest->device_model,
                    'services_count' => $orders->count(),
                    'last_service_at' => $latest->created_at,
                    'last_status' => $latest->kanbanColumn?->name ?? $this->serviceOrderStatusLabel($latest->status),
                ];
            })
            ->values();

        $checkoutOrders = $customer->checkoutOrders()
            ->with(['tenant', 'items', 'customerAddress'])
            ->latest()
            ->get();

        $addresses = $request->user()->customerAddresses()
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->latest('is_default')
            ->latest()
            ->get();

        $allAssistances = Customer::with(['tenant', 'serviceOrders', 'checkoutOrders'])
            ->where('user_id', $request->user()->id)
            ->whereHas('tenant', fn ($query) => $query->whereIn('status', ['active', 'trial']))
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('public.account.index', compact(
            'tenant',
            'customer',
            'serviceOrders',
            'equipment',
            'checkoutOrders',
            'addresses',
            'allAssistances'
        ));
    }

    public function updateProfile(Request $request, string $slug)
    {
        [$tenant, $customer] = $this->tenantAndCustomer($request, $slug);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'cpf' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        DB::transaction(function () use ($request, $customer, $validated) {
            $request->user()->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            $customer->update($validated);
        });

        return redirect()
            ->route('public.account.index', $tenant->slug)
            ->with('success', 'Dados atualizados.');
    }

    public function storeAddress(Request $request, string $slug)
    {
        [$tenant] = $this->tenantAndCustomer($request, $slug);
        $validated = $this->validatedAddress($request);

        DB::transaction(function () use ($request, $tenant, $validated) {
            if ($validated['is_default'] ?? false) {
                $this->clearDefaultAddresses($request, $tenant);
            }

            $request->user()->customerAddresses()->create(array_merge($validated, [
                'tenant_id' => $tenant->id,
            ]));
        });

        return redirect()
            ->route('public.account.index', $tenant->slug)
            ->with('success', 'Endereço cadastrado.');
    }

    public function updateAddress(Request $request, string $slug, CustomerAddress $address)
    {
        [$tenant] = $this->tenantAndCustomer($request, $slug);
        $this->authorizeAddress($request, $tenant, $address);
        $validated = $this->validatedAddress($request);

        DB::transaction(function () use ($request, $tenant, $address, $validated) {
            if ($validated['is_default'] ?? false) {
                $this->clearDefaultAddresses($request, $tenant);
            }

            $address->update($validated);
        });

        return redirect()
            ->route('public.account.index', $tenant->slug)
            ->with('success', 'Endereço atualizado.');
    }

    public function destroyAddress(Request $request, string $slug, CustomerAddress $address)
    {
        [$tenant] = $this->tenantAndCustomer($request, $slug);
        $this->authorizeAddress($request, $tenant, $address);

        $address->delete();

        return redirect()
            ->route('public.account.index', $tenant->slug)
            ->with('success', 'Endereço removido.');
    }

    private function tenantAndCustomer(Request $request, string $slug): array
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        $tenant->enforceSubscriptionLifecycle();
        abort_unless($tenant->isPubliclyAvailable(), 404);

        if (! $request->user()) {
            throw new HttpResponseException(redirect()->route('public.customer.login', [
                'slug' => $tenant->slug,
                'intended' => '/'.$tenant->slug.'/minha-conta',
            ]));
        }

        abort_unless($request->user()->isCustomer(), 403);

        $request->session()->put('public_tenant_id', $tenant->id);
        $request->session()->put('public_tenant_slug', $tenant->slug);

        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $request->user()->id,
            ],
            [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ]
        );

        return [$tenant, $customer];
    }

    private function validatedAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeAddress(Request $request, Tenant $tenant, CustomerAddress $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 404);
        abort_unless($address->tenant_id === null || $address->tenant_id === $tenant->id, 404);
    }

    private function clearDefaultAddresses(Request $request, Tenant $tenant): void
    {
        $request->user()->customerAddresses()
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->update(['is_default' => false]);
    }

    private function serviceOrderStatusLabel(string $status): string
    {
        return [
            'pending' => 'Pendente',
            'budgeting' => 'Orçamento',
            'approved' => 'Orçamento aprovado',
            'rejected' => 'Orçamento recusado',
            'finished' => 'Finalizado',
        ][$status] ?? $status;
    }
}
