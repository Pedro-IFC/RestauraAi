<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CheckoutOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CheckoutOrderController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $stateLabels = CheckoutOrder::auditStateLabels();

        $orders = CheckoutOrder::where('tenant_id', $tenantId)
            ->where('type', 'products')
            ->with(['customer', 'items', 'statusHistories' => fn ($query) => $query->latest()])
            ->when($request->filled('state'), function ($query) use ($request) {
                $this->applyStateFilter($query, $request->string('state')->toString());
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('tenant.checkout_orders.index', compact('orders', 'stateLabels'));
    }

    public function show(CheckoutOrder $order)
    {
        $this->authorizeTenantOrder($order);

        $order->load([
            'customer',
            'customerAddress',
            'items',
            'statusHistories' => fn ($query) => $query->with('user')->oldest(),
        ]);

        $stateLabels = CheckoutOrder::auditStateLabels();

        return view('tenant.checkout_orders.show', compact('order', 'stateLabels'));
    }

    public function updateStatus(Request $request, CheckoutOrder $order)
    {
        $this->authorizeTenantOrder($order);

        $stateLabels = CheckoutOrder::auditStateLabels();
        $validated = $request->validate([
            'state' => ['required', Rule::in(array_keys($stateLabels))],
            'reason' => [
                Rule::requiredIf(fn () => in_array($request->input('state'), [
                    CheckoutOrder::STATE_REFUSED,
                    CheckoutOrder::STATE_CANCELED,
                ], true)),
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $order->transitionTo($validated['state'], $request->user(), $validated['reason'] ?? null);

        return redirect()
            ->route('tenant.checkout-orders.show', $order)
            ->with('success', 'Status do pedido atualizado.');
    }

    private function authorizeTenantOrder(CheckoutOrder $order): void
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id && $order->type === 'products', 404);
    }

    private function applyStateFilter($query, string $state): void
    {
        match ($state) {
            CheckoutOrder::STATE_AWAITING_PAYMENT => $query
                ->where('status', CheckoutOrder::STATUS_OPEN)
                ->where('fulfillment_status', CheckoutOrder::FULFILLMENT_PENDING),
            CheckoutOrder::STATE_PAID => $query
                ->where('status', CheckoutOrder::STATUS_PAID)
                ->where('fulfillment_status', CheckoutOrder::FULFILLMENT_PENDING),
            CheckoutOrder::STATE_PREPARING => $query
                ->where('fulfillment_status', CheckoutOrder::FULFILLMENT_PREPARING),
            CheckoutOrder::STATE_READY_FOR_PICKUP => $query
                ->where('fulfillment_status', CheckoutOrder::FULFILLMENT_READY_FOR_PICKUP),
            CheckoutOrder::STATE_SHIPPED => $query
                ->where('fulfillment_status', CheckoutOrder::FULFILLMENT_OUT_FOR_DELIVERY),
            CheckoutOrder::STATE_REFUSED => $query->where('status', CheckoutOrder::STATUS_REFUSED),
            CheckoutOrder::STATE_CANCELED => $query->where('status', CheckoutOrder::STATUS_CANCELED),
            default => null,
        };
    }
}
