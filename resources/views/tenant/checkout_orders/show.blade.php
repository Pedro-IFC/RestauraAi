@extends('layouts.app')

@section('title', 'Pedido #' . $order->id)

@section('content')
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <a href="{{ route('tenant.checkout-orders.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Voltar para pedidos</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Pedido #{{ $order->id }}</h1>
            <p class="text-sm text-gray-600">Estado atual: {{ $order->auditStateLabel() }}</p>
        </div>
        <div class="text-left md:text-right">
            <div class="text-sm text-gray-500">Total</div>
            <div class="text-2xl font-bold text-gray-900">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <div class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
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

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Historico cronologico</h2>
                <div class="mt-5 space-y-4">
                    @forelse ($order->statusHistories as $history)
                        <article class="border-l-2 border-yellow-300 pl-4">
                            <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $stateLabels[$history->to_state] ?? $history->to_state }}</h3>
                                    <p class="text-sm text-gray-600">
                                        Alterado por {{ $history->user?->name ?? 'Sistema' }}
                                        em {{ $history->created_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                                @if ($history->from_state)
                                    <span class="text-xs font-semibold text-gray-500">
                                        {{ $stateLabels[$history->from_state] ?? $history->from_state }} &rarr; {{ $stateLabels[$history->to_state] ?? $history->to_state }}
                                    </span>
                                @endif
                            </div>
                            @if ($history->reason)
                                <p class="mt-2 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">{{ $history->reason }}</p>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                            Nenhuma movimentacao registrada para este pedido.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Atualizar estado</h2>
                <form method="POST" action="{{ route('tenant.checkout-orders.status', $order) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700">Novo estado</label>
                        <select id="state" name="state" required class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach ($stateLabels as $state => $label)
                                <option value="{{ $state }}" @selected(old('state', $order->currentAuditState()) === $state)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="reason" class="block text-sm font-medium text-gray-700">Motivo</label>
                        <textarea id="reason" name="reason" rows="4" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Obrigatorio para recusas e cancelamentos.">{{ old('reason') }}</textarea>
                    </div>

                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Salvar status
                    </button>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Resumo</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-600">Cliente</dt>
                        <dd class="font-semibold text-gray-900">{{ $order->customer?->name ?? 'Nao vinculado' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-600">Pagamento</dt>
                        <dd class="font-semibold text-gray-900">{{ $order->payment_method === 'card' ? 'Cartao' : 'Pix' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-600">Entrega/retirada</dt>
                        <dd class="font-semibold text-gray-900">{{ $order->fulfillmentMethodLabel() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-600">FixGo</dt>
                        <dd class="font-semibold text-gray-900">R$ {{ number_format((float) $order->fixgo_commission_amount, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-gray-600">Assistencia</dt>
                        <dd class="font-semibold text-gray-900">R$ {{ number_format((float) $order->tenant_amount, 2, ',', '.') }}</dd>
                    </div>
                </dl>

                @if ($order->customerAddress)
                    <div class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                        <div class="font-semibold text-gray-900">Endereco</div>
                        <div class="mt-1">{{ $order->customerAddress->formatted() }}</div>
                    </div>
                @endif
            </section>
        </aside>
    </div>
@endsection
