@extends('layouts.app')

@section('title', $item->name)

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $item->name }}</h1>
            <p class="text-sm text-gray-600">{{ $item->type === 'product' ? 'Produto de venda' : 'Insumo técnico' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('estoque.edit', $item) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Editar</a>
            <form method="POST" action="{{ route('estoque.destroy', $item) }}">
                @csrf
                @method('DELETE')
                <button class="rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Remover</button>
            </form>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Catálogo</div>
            <div class="mt-2 text-lg font-bold {{ $item->is_for_sale ? 'text-green-700' : 'text-gray-700' }}">{{ $item->is_for_sale ? 'Visível' : 'Oculto' }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Estoque</div>
            <div class="mt-2 text-lg font-bold text-gray-900">{{ number_format((float) $item->stock_quantity, 2, ',', '.') }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Custo unitário</div>
            <div class="mt-2 text-lg font-bold text-gray-900">R$ {{ number_format((float) $item->cost_price, 2, ',', '.') }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Margem unitária</div>
            <div class="mt-2 text-lg font-bold text-gray-900">R$ {{ number_format($item->gross_margin, 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="font-semibold text-gray-900">Últimas OS com uso deste item</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($item->serviceOrders as $order)
                <a href="{{ route('ordens-servico.show', $order) }}" class="block px-4 py-3 text-sm hover:bg-gray-50">
                    <span class="font-semibold text-gray-900">OS #{{ $order->id }}</span>
                    <span class="text-gray-500">- {{ $order->device_model }}</span>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">Ainda não foi usado em nenhuma OS.</div>
            @endforelse
        </div>
    </div>
@endsection
