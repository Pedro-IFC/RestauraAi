@extends('layouts.app')

@section('title', 'Carrinho')

@section('content')
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Carrinho de compras</h1>
        </div>
        <a href="{{ route('public.store.index', $tenant->slug) }}" class="w-fit rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Continuar comprando
        </a>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="font-semibold text-gray-900">Itens</h2>
            <div class="mt-4 divide-y divide-gray-100">
                @forelse ($cartItems as $item)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div>
                            <div class="font-medium text-gray-900">{{ $item['name'] }}</div>
                            <div class="text-sm text-gray-500">{{ number_format($item['quantity'], 2, ',', '.') }} x R$ {{ number_format($item['unit_price'], 2, ',', '.') }}</div>
                        </div>
                        <div class="text-right font-semibold text-gray-900">R$ {{ number_format($item['total_price'], 2, ',', '.') }}</div>
                    </div>
                @empty
                    <div class="py-10 text-center text-sm text-gray-500">Seu carrinho está vazio.</div>
                @endforelse
            </div>
        </section>

        <aside class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Resumo</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">Total</dt>
                    <dd class="font-semibold text-gray-900">R$ {{ number_format($split['total'], 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">Comissão FixGo</dt>
                    <dd class="font-semibold text-gray-900">R$ {{ number_format($split['fixgo'], 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-600">Repasse assistência</dt>
                    <dd class="font-semibold text-gray-900">R$ {{ number_format($split['tenant'], 2, ',', '.') }}</dd>
                </div>
            </dl>

            <form method="POST" action="{{ route('public.checkout.process', $tenant->slug) }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="payment_method" class="block text-sm font-medium text-gray-700">Forma pretendida</label>
                    <select id="payment_method" name="payment_method" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="pix">Pix</option>
                        <option value="card">Cartão</option>
                    </select>
                </div>
                <button @disabled($cartItems->isEmpty())
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                    Criar pedido em aberto
                </button>
            </form>
            <p class="mt-3 text-xs text-gray-500">Nenhum pagamento real será processado neste momento.</p>
        </aside>
    </div>
@endsection
