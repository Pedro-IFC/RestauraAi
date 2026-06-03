@extends('layouts.app')

@section('title', 'Pedido #' . $order->id)

@section('content')
    @php
        $statusLabels = [
            'open' => 'Em aberto',
            'canceled' => 'Cancelado',
            'paid' => 'Pago',
        ];
        $methodLabels = [
            'pix' => 'Pix',
            'card' => 'Cartão',
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Pedido #{{ $order->id }}</h1>
        </div>
        <span class="w-fit rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">
            {{ $statusLabels[$order->status] ?? $order->status }}
        </span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="font-semibold text-gray-900">Itens do pedido</h2>
            <div class="mt-4 divide-y divide-gray-100">
                @foreach ($order->items as $item)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <div class="font-medium text-gray-900">{{ $item->description }}</div>
                            <div class="text-sm text-gray-500">{{ number_format((float) $item->quantity, 2, ',', '.') }} x R$ {{ number_format((float) $item->unit_price, 2, ',', '.') }}</div>
                        </div>
                        <div class="text-right font-semibold text-gray-900">R$ {{ number_format((float) $item->total_price, 2, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Pagamento</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">Forma pretendida</dt>
                    <dd class="font-semibold text-gray-900">{{ $methodLabels[$order->payment_method] ?? 'A definir' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">Total</dt>
                    <dd class="font-semibold text-gray-900">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">FixGo</dt>
                    <dd class="font-semibold text-gray-900">R$ {{ number_format((float) $order->fixgo_commission_amount, 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">Assistência</dt>
                    <dd class="font-semibold text-gray-900">R$ {{ number_format((float) $order->tenant_amount, 2, ',', '.') }}</dd>
                </div>
            </dl>

            <div class="mt-5 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                Integração de pagamento ainda não ativada. Este pedido permanece em aberto para implementação futura.
            </div>

            @if ($order->canBeCanceled())
                <form method="POST" action="{{ route('public.checkout.order.cancel', [$tenant->slug, $order]) }}" class="mt-4">
                    @csrf
                    @method('PATCH')
                    <button class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Cancelar pedido
                    </button>
                </form>
            @elseif ($order->canceled_at)
                <p class="mt-4 text-sm text-gray-500">Cancelado em {{ $order->canceled_at->format('d/m/Y H:i') }}.</p>
            @endif
        </aside>
    </div>
@endsection
