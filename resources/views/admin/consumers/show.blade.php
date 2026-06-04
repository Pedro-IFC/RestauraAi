@extends('layouts.admin')

@section('title', 'Consumidor '.$consumer->id)

@section('content')
    @php
        $serviceOrdersCount = $consumer->customers->sum(fn ($customer) => $customer->serviceOrders->count());
        $checkoutOrdersCount = $consumer->customers->sum(fn ($customer) => $customer->checkoutOrders->count());
    @endphp

    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $consumer->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $consumer->email }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.consumers.export', $consumer) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Exportar JSON LGPD
            </a>
            <a href="{{ route('admin.consumers.index') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Voltar
            </a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-gray-600">Assistências</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $consumer->customers->count() }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-gray-600">Ordens de Serviço</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $serviceOrdersCount }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-gray-600">Pedidos</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $checkoutOrdersCount }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-sm text-gray-600">Endereços</div>
            <div class="mt-2 text-2xl font-bold text-gray-900">{{ $consumer->customerAddresses->count() }}</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="space-y-6">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Vínculos por assistência</h2>
                <div class="mt-4 divide-y divide-gray-100">
                    @forelse ($consumer->customers as $customer)
                        <article class="py-4 first:pt-0 last:pb-0">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $customer->tenant?->name ?? 'Assistência removida' }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $customer->email ?? 'E-mail não informado' }}
                                        @if ($customer->cpf)
                                            - CPF {{ $customer->cpf }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-sm text-gray-700 md:text-right">
                                    {{ $customer->serviceOrders->count() }} OS
                                    <span class="text-gray-400">-</span>
                                    {{ $customer->checkoutOrders->count() }} pedido(s)
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-lg bg-gray-50 p-3">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Últimas OS</div>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        @forelse ($customer->serviceOrders->take(5) as $order)
                                            <div>#{{ $order->id }} - {{ $order->device_model }} - {{ $order->kanbanColumn?->name ?? $order->status }}</div>
                                        @empty
                                            <div class="text-gray-500">Sem ordens de serviço.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="rounded-lg bg-gray-50 p-3">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Últimos pedidos</div>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        @forelse ($customer->checkoutOrders->take(5) as $order)
                                            <div>#{{ $order->id }} - {{ $order->status }} - R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}</div>
                                        @empty
                                            <div class="text-gray-500">Sem pedidos.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="py-8 text-center text-sm text-gray-500">Nenhum vínculo com assistência.</div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Endereços cadastrados</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse ($consumer->customerAddresses as $address)
                        <article class="rounded-lg border border-gray-200 p-4">
                            <div class="font-semibold text-gray-900">{{ $address->label }}</div>
                            <p class="mt-2 text-sm text-gray-700">{{ $address->formatted() }}</p>
                            <p class="mt-2 text-xs text-gray-500">{{ $address->tenant?->name ?? 'Global do consumidor' }}</p>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 md:col-span-2">
                            Nenhum endereço cadastrado.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <aside class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-red-700">Direito ao esquecimento</h2>
            <p class="mt-2 text-sm text-gray-700">
                Esta ação exclui definitivamente a conta global do consumidor, remove endereços e códigos de acesso,
                encerra sessões e anonimiza dados pessoais nos cadastros de cliente preservados para registros transacionais.
            </p>

            <form method="POST" action="{{ route('admin.consumers.destroy', $consumer) }}" class="mt-5 space-y-4">
                @csrf
                @method('DELETE')
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="confirm" value="1" required class="mt-1 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    Confirmo a solicitação LGPD de exclusão definitiva desta conta.
                </label>
                <button class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Excluir e anonimizar
                </button>
            </form>
        </aside>
    </div>
@endsection
