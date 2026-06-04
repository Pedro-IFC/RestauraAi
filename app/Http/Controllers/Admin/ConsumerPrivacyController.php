<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MagicLoginCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsumerPrivacyController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->where('role', 'customer')
            ->withCount(['customers', 'customerAddresses']);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhereHas('customers', function ($customerQuery) use ($search) {
                        $customerQuery
                            ->where('cpf', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    });
            });
        }

        $consumers = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.consumers.index', compact('consumers'));
    }

    public function show(User $consumer)
    {
        $this->abortUnlessCustomer($consumer);

        $consumer->load([
            'customers.tenant',
            'customers.serviceOrders.tenant',
            'customers.serviceOrders.kanbanColumn',
            'customers.checkoutOrders.tenant',
            'customers.checkoutOrders.items',
            'customers.checkoutOrders.customerAddress',
            'customerAddresses.tenant',
        ]);

        return view('admin.consumers.show', compact('consumer'));
    }

    public function export(User $consumer)
    {
        $this->abortUnlessCustomer($consumer);

        $consumer->load([
            'customers.tenant',
            'customers.serviceOrders.tenant',
            'customers.serviceOrders.kanbanColumn',
            'customers.checkoutOrders.tenant',
            'customers.checkoutOrders.items',
            'customers.checkoutOrders.customerAddress',
            'customerAddresses.tenant',
        ]);

        $payload = [
            'exported_at' => now()->toIso8601String(),
            'consumer' => [
                'id' => $consumer->id,
                'name' => $consumer->name,
                'email' => $consumer->email,
                'created_at' => $consumer->created_at?->toIso8601String(),
            ],
            'analytics' => [
                'tenant_links' => $consumer->customers->count(),
                'service_orders' => $consumer->customers->sum(fn (Customer $customer) => $customer->serviceOrders->count()),
                'checkout_orders' => $consumer->customers->sum(fn (Customer $customer) => $customer->checkoutOrders->count()),
                'addresses' => $consumer->customerAddresses->count(),
            ],
            'customers' => $consumer->customers->map(fn (Customer $customer) => [
                'tenant' => [
                    'id' => $customer->tenant?->id,
                    'name' => $customer->tenant?->name,
                    'slug' => $customer->tenant?->slug,
                ],
                'profile' => [
                    'name' => $customer->name,
                    'cpf' => $customer->cpf,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'created_at' => $customer->created_at?->toIso8601String(),
                ],
                'service_orders' => $customer->serviceOrders->map(fn ($order) => [
                    'id' => $order->id,
                    'tenant' => $order->tenant?->slug,
                    'device_model' => $order->device_model,
                    'status' => $order->status,
                    'kanban_column' => $order->kanbanColumn?->name,
                    'total_price' => (string) $order->total_price,
                    'created_at' => $order->created_at?->toIso8601String(),
                ])->values(),
                'checkout_orders' => $customer->checkoutOrders->map(fn ($order) => [
                    'id' => $order->id,
                    'tenant' => $order->tenant?->slug,
                    'type' => $order->type,
                    'status' => $order->status,
                    'payment_method' => $order->payment_method,
                    'fulfillment_method' => $order->fulfillment_method,
                    'fulfillment_status' => $order->fulfillment_status,
                    'total_amount' => (string) $order->total_amount,
                    'items' => $order->items->map(fn ($item) => [
                        'description' => $item->description,
                        'quantity' => (string) $item->quantity,
                        'total_price' => (string) $item->total_price,
                    ])->values(),
                    'created_at' => $order->created_at?->toIso8601String(),
                ])->values(),
            ])->values(),
            'addresses' => $consumer->customerAddresses->map(fn ($address) => [
                'tenant' => $address->tenant?->slug,
                'label' => $address->label,
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone,
                'postal_code' => $address->postal_code,
                'street' => $address->street,
                'number' => $address->number,
                'complement' => $address->complement,
                'neighborhood' => $address->neighborhood,
                'city' => $address->city,
                'state' => $address->state,
            ])->values(),
        ];

        return response()
            ->json($payload, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ->header('Content-Disposition', 'attachment; filename="consumidor-'.$consumer->id.'-lgpd.json"');
    }

    public function destroy(Request $request, User $consumer)
    {
        $this->abortUnlessCustomer($consumer);

        $validated = $request->validate([
            'confirm' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($consumer, $validated) {
            $email = $consumer->email;
            $anonymizedName = 'Consumidor removido #'.$consumer->id;

            Customer::where('user_id', $consumer->id)->update([
                'user_id' => null,
                'name' => $anonymizedName,
                'cpf' => null,
                'phone' => null,
                'email' => null,
            ]);

            MagicLoginCode::where('email', $email)->delete();
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            DB::table('sessions')->where('user_id', $consumer->id)->delete();

            $consumer->delete();
        });

        return redirect()
            ->route('admin.consumers.index')
            ->with('success', 'Conta de consumidor excluída e dados pessoais anonimizados.');
    }

    private function abortUnlessCustomer(User $consumer): void
    {
        abort_unless($consumer->role === 'customer', 404);
    }
}
