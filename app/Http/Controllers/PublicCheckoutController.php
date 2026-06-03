<?php

namespace App\Http\Controllers;

use App\Models\CheckoutOrder;
use App\Models\Item;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicCheckoutController extends Controller
{
    private const FIXGO_COMMISSION_RATE = 10.00;

    public function cart(Request $request, $slug)
    {
        $tenant = $this->publicTenant($slug);
        abort_unless($tenant->hasFeature('catalog'), 404);

        $cartItems = $this->cartItems($request, $tenant);
        $split = $this->splitFor($cartItems->sum('total_price'));

        return view('public.checkout.cart', compact('tenant', 'cartItems', 'split'));
    }

    public function addProduct(Request $request, $slug)
    {
        $tenant = $this->publicTenant($slug);
        abort_unless($tenant->hasFeature('catalog'), 404);

        $validated = $request->validate([
            'item_id' => 'required|integer',
            'quantity' => 'required|numeric|min:1',
        ]);

        $item = Item::forTenant($tenant->id)
            ->publicCatalog()
            ->findOrFail($validated['item_id']);

        $quantity = (float) $validated['quantity'];

        if ((float) $item->stock_quantity < $quantity) {
            return back()->withErrors(['quantity' => 'Quantidade indisponível no estoque.']);
        }

        $cart = $request->session()->get($this->cartSessionKey($tenant), []);
        $currentQuantity = (float) ($cart[$item->id]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ((float) $item->stock_quantity < $newQuantity) {
            return back()->withErrors(['quantity' => 'Quantidade indisponível no estoque.']);
        }

        $cart[$item->id] = [
            'item_id' => $item->id,
            'name' => $item->name,
            'quantity' => $newQuantity,
            'unit_price' => (float) $item->sale_price,
        ];

        $request->session()->put($this->cartSessionKey($tenant), $cart);

        return redirect()
            ->route('public.checkout.cart', $tenant->slug)
            ->with('success', 'Produto adicionado ao carrinho.');
    }

    public function process(Request $request, $slug)
    {
        $tenant = $this->publicTenant($slug);
        abort_unless($tenant->hasFeature('catalog'), 404);

        $validated = $request->validate([
            'payment_method' => 'required|in:pix,card',
        ]);

        $cartItems = $this->cartItems($request, $tenant);

        if ($cartItems->isEmpty()) {
            return redirect()
                ->route('public.checkout.cart', $tenant->slug)
                ->withErrors(['cart' => 'Seu carrinho está vazio.']);
        }

        try {
            $order = DB::transaction(function () use ($tenant, $cartItems, $validated) {
                $itemsById = Item::forTenant($tenant->id)
                    ->publicCatalog()
                    ->lockForUpdate()
                    ->whereIn('id', $cartItems->pluck('item_id'))
                    ->get()
                    ->keyBy('id');

                foreach ($cartItems as $cartItem) {
                    $item = $itemsById->get($cartItem['item_id']);

                    if (! $item || (float) $item->stock_quantity < (float) $cartItem['quantity']) {
                        throw new \RuntimeException('Um dos produtos não está mais disponível na quantidade solicitada.');
                    }
                }

                $split = $this->splitFor($cartItems->sum('total_price'));
                $order = CheckoutOrder::create([
                    'tenant_id' => $tenant->id,
                    'type' => 'products',
                    'status' => CheckoutOrder::STATUS_OPEN,
                    'payment_method' => $validated['payment_method'],
                    'total_amount' => $split['total'],
                    'fixgo_commission_rate' => self::FIXGO_COMMISSION_RATE,
                    'fixgo_commission_amount' => $split['fixgo'],
                    'tenant_amount' => $split['tenant'],
                ]);

                foreach ($cartItems as $cartItem) {
                    $order->items()->create([
                        'item_id' => $cartItem['item_id'],
                        'description' => $cartItem['name'],
                        'quantity' => $cartItem['quantity'],
                        'unit_price' => $cartItem['unit_price'],
                        'total_price' => $cartItem['total_price'],
                    ]);
                }

                return $order;
            });
        } catch (ModelNotFoundException) {
            abort(404);
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('public.checkout.cart', $tenant->slug)
                ->withErrors(['cart' => $exception->getMessage()]);
        }

        $request->session()->forget($this->cartSessionKey($tenant));

        return redirect()
            ->route('public.checkout.order.show', [$tenant->slug, $order])
            ->with('success', 'Pedido criado em aberto. Nenhuma cobrança real foi processada.');
    }

    public function createBudgetOrder(Request $request, $slug, $os_id)
    {
        $tenant = $this->publicTenant($slug);
        $validated = $request->validate([
            'lookup' => 'required|string|max:30',
            'payment_method' => 'required|in:pix,card',
        ]);

        $serviceOrder = $this->serviceOrderForLookup($tenant, $os_id, $validated['lookup']);

        abort_unless($serviceOrder->status === 'approved' && (float) $serviceOrder->total_price > 0, 404);

        $split = $this->splitFor((float) $serviceOrder->total_price);

        $order = DB::transaction(function () use ($tenant, $serviceOrder, $validated, $split) {
            $order = CheckoutOrder::create([
                'tenant_id' => $tenant->id,
                'service_order_id' => $serviceOrder->id,
                'type' => 'service_order_budget',
                'status' => CheckoutOrder::STATUS_OPEN,
                'payment_method' => $validated['payment_method'],
                'total_amount' => $split['total'],
                'fixgo_commission_rate' => self::FIXGO_COMMISSION_RATE,
                'fixgo_commission_amount' => $split['fixgo'],
                'tenant_amount' => $split['tenant'],
            ]);

            $order->items()->create([
                'description' => 'Orçamento aprovado - OS #'.$serviceOrder->id,
                'quantity' => 1,
                'unit_price' => $split['total'],
                'total_price' => $split['total'],
            ]);

            return $order;
        });

        return redirect()
            ->route('public.checkout.order.show', [$tenant->slug, $order])
            ->with('success', 'Pedido de pagamento do orçamento criado em aberto.');
    }

    public function showOrder($slug, CheckoutOrder $order)
    {
        $tenant = $this->publicTenant($slug);

        abort_unless($order->tenant_id === $tenant->id, 404);

        $order->load(['items', 'serviceOrder']);

        return view('public.checkout.order', compact('tenant', 'order'));
    }

    public function cancelOrder(Request $request, $slug, CheckoutOrder $order)
    {
        $tenant = $this->publicTenant($slug);

        abort_unless($order->tenant_id === $tenant->id, 404);
        abort_unless($order->canBeCanceled(), 409);

        $order->update([
            'status' => CheckoutOrder::STATUS_CANCELED,
            'canceled_at' => now(),
        ]);

        return redirect()
            ->route('public.checkout.order.show', [$tenant->slug, $order])
            ->with('success', 'Pedido cancelado.');
    }

    private function publicTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        $tenant->enforceSubscriptionLifecycle();

        abort_unless($tenant->isPubliclyAvailable(), 404);

        return $tenant;
    }

    private function cartItems(Request $request, Tenant $tenant)
    {
        return collect($request->session()->get($this->cartSessionKey($tenant), []))
            ->map(function (array $cartItem) {
                $quantity = (float) $cartItem['quantity'];
                $unitPrice = (float) $cartItem['unit_price'];

                return array_merge($cartItem, [
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $quantity * $unitPrice,
                ]);
            })
            ->values();
    }

    private function cartSessionKey(Tenant $tenant): string
    {
        return 'checkout_cart.'.$tenant->id;
    }

    private function splitFor(float $total): array
    {
        $fixgo = round($total * (self::FIXGO_COMMISSION_RATE / 100), 2);

        return [
            'total' => round($total, 2),
            'fixgo' => $fixgo,
            'tenant' => round($total - $fixgo, 2),
        ];
    }

    private function serviceOrderForLookup(Tenant $tenant, int $serviceOrderId, string $lookup): ServiceOrder
    {
        $digits = preg_replace('/\D+/', '', $lookup);

        return ServiceOrder::where('tenant_id', $tenant->id)
            ->where('id', $serviceOrderId)
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
            ->firstOrFail();
    }
}
