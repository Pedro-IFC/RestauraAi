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

    @if (! auth()->user()?->isCustomer())
        <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-semibold text-blue-900">Checkout mais rápido</h2>
                    <p class="mt-1 text-sm text-blue-800">Entre com um código por e-mail para vincular este pedido ao seu histórico.</p>
                </div>
                <a href="{{ route('public.customer.login', ['slug' => $tenant->slug, 'intended' => '/'.$tenant->slug.'/checkout']) }}"
                    class="w-fit rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Receber código
                </a>
            </div>
        </div>
    @endif

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
            @if (auth()->user()?->isCustomer())
                <div class="mt-4 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                    <div class="font-semibold text-gray-900">Dados de faturamento</div>
                    <div class="mt-1">{{ $customer?->name ?? auth()->user()->name }}</div>
                    <div>{{ $customer?->email ?? auth()->user()->email }}</div>
                    @if ($customer?->cpf)
                        <div>CPF {{ $customer->cpf }}</div>
                    @endif
                    @if ($customer?->phone)
                        <div>{{ $customer->phone }}</div>
                    @endif
                </div>
            @endif
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
                <div>
                    <label for="fulfillment_method" class="block text-sm font-medium text-gray-700">Entrega ou retirada</label>
                    <select id="fulfillment_method" name="fulfillment_method" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="pickup">Retirada na assistência</option>
                        <option value="delivery" @disabled(! auth()->user()?->isCustomer() || $addresses->isEmpty())>Entrega em endereço cadastrado</option>
                    </select>
                    @if (! auth()->user()?->isCustomer() || $addresses->isEmpty())
                        <p class="mt-1 text-xs text-gray-500">Para entrega, entre e cadastre um endereço em Minha Conta.</p>
                    @endif
                </div>
                @if (auth()->user()?->isCustomer() && $addresses->isNotEmpty())
                    <div>
                        <label for="customer_address_id" class="block text-sm font-medium text-gray-700">Endereço de entrega</label>
                        <select id="customer_address_id" name="customer_address_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Selecione se escolher entrega</option>
                            @foreach ($addresses as $address)
                                <option value="{{ $address->id }}" @selected($address->is_default)>
                                    {{ $address->label }} - {{ $address->formatted() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if (! auth()->user()?->isCustomer())
                    <a href="{{ route('public.customer.login', ['slug' => $tenant->slug, 'intended' => '/'.$tenant->slug.'/checkout']) }}"
                        class="block w-full rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700">
                        Entrar para finalizar
                    </a>
                @else
                    <button @disabled($cartItems->isEmpty())
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300">
                        Criar pedido em aberto
                    </button>
                @endif
            </form>
            <p class="mt-3 text-xs text-gray-500">Nenhum pagamento real será processado neste momento.</p>
        </aside>
    </div>
@endsection
