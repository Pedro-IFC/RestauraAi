@extends('layouts.app')

@section('title', 'Minha Conta - '.$tenant->name)

@section('content')
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Minha Conta</h1>
            <p class="mt-1 text-sm text-gray-600">Gerencie seus dados e acompanhe seu histórico nesta assistência.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('public.os.create', $tenant->slug) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Abrir chamado
            </a>
            <a href="{{ route('public.store.index', $tenant->slug) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Ver catálogo
            </a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
        <aside class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Informações básicas</h2>
                <form method="POST" action="{{ route('public.account.profile.update', $tenant->slug) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input id="name" name="name" value="{{ old('name', $customer->name) }}" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                        <input id="cpf" name="cpf" value="{{ old('cpf', $customer->cpf) }}"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Telefone</label>
                        <input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Salvar dados
                    </button>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Novo endereço</h2>
                <form method="POST" action="{{ route('public.account.addresses.store', $tenant->slug) }}" class="mt-4 space-y-3">
                    @csrf
                    @include('public.account.partials.address-form')
                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Adicionar endereço
                    </button>
                </form>
            </section>
        </aside>

        <div class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="font-semibold text-gray-900">Central de Ordens de Serviço</h2>
                    <span class="text-sm text-gray-500">{{ $serviceOrders->count() }} ordem(ns)</span>
                </div>

                <div class="mt-4 space-y-4">
                    @forelse ($serviceOrders as $order)
                        @include('public.tracking.partials.service-order-card', ['order' => $order, 'tenant' => $tenant])
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                            Nenhuma ordem de serviço vinculada à sua conta nesta assistência.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="font-semibold text-gray-900">Meus Equipamentos</h2>
                    <span class="text-sm text-gray-500">{{ $equipment->count() }} cadastrado(s)</span>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse ($equipment as $item)
                        <article class="rounded-lg border border-gray-200 p-4">
                            <h3 class="font-semibold text-gray-900">{{ $item['device_model'] }}</h3>
                            <dl class="mt-3 space-y-1 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-600">Manutenções</dt>
                                    <dd class="font-medium text-gray-900">{{ $item['services_count'] }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-600">Último status</dt>
                                    <dd class="font-medium text-gray-900">{{ $item['last_status'] }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-gray-600">Última entrada</dt>
                                    <dd class="font-medium text-gray-900">{{ $item['last_service_at']->format('d/m/Y') }}</dd>
                                </div>
                            </dl>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 md:col-span-2">
                            Nenhum equipamento vinculado à sua conta nesta assistência.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="font-semibold text-gray-900">Histórico de Pedidos</h2>
                    <span class="text-sm text-gray-500">{{ $checkoutOrders->count() }} pedido(s)</span>
                </div>

                <div class="mt-4 divide-y divide-gray-100">
                    @forelse ($checkoutOrders as $order)
                        <article class="py-4 first:pt-0 last:pb-0">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900">Pedido #{{ $order->id }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $order->type === 'products' ? 'Produtos do catálogo' : 'Pagamento de orçamento' }}
                                        - {{ $order->created_at->format('d/m/Y H:i') }}
                                    </p>
                                    <div class="mt-2 text-sm text-gray-700">
                                        @foreach ($order->items as $orderItem)
                                            <div>{{ $orderItem->description }} x {{ number_format((float) $orderItem->quantity, 2, ',', '.') }}</div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="text-left md:text-right">
                                    <div class="text-lg font-bold text-gray-900">R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</div>
                                    <div class="mt-1 text-sm text-gray-600">{{ $order->fulfillmentMethodLabel() }} - {{ $order->fulfillmentStatusLabel() }}</div>
                                    @if ($order->customerAddress)
                                        <div class="mt-1 max-w-xs text-sm text-gray-500">{{ $order->customerAddress->formatted() }}</div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                            Nenhum pedido de produto vinculado à sua conta nesta assistência.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="font-semibold text-gray-900">Endereços de Entrega</h2>
                    <span class="text-sm text-gray-500">{{ $addresses->count() }} endereço(s)</span>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse ($addresses as $address)
                        <article class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $address->label }}</h3>
                                    @if ($address->is_default)
                                        <span class="mt-1 inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">Padrão</span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('public.account.addresses.destroy', [$tenant->slug, $address]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-sm font-semibold text-red-600 hover:text-red-700">Remover</button>
                                </form>
                            </div>
                            <p class="mt-3 text-sm text-gray-700">{{ $address->formatted() }}</p>

                            <form method="POST" action="{{ route('public.account.addresses.update', [$tenant->slug, $address]) }}" class="mt-4 space-y-3 border-t border-gray-100 pt-4">
                                @csrf
                                @method('PATCH')
                                @include('public.account.partials.address-form', ['address' => $address])
                                <button class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Atualizar endereço
                                </button>
                            </form>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 md:col-span-2">
                            Nenhum endereço cadastrado para entrega ou devolução de aparelhos.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection
