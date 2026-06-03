@extends('layouts.app')

@section('title', 'Estoque')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Estoque</h1>
            <p class="text-sm text-gray-600">Controle produtos de venda, insumos técnicos e alertas de baixa.</p>
        </div>
        <a href="{{ route('estoque.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            Novo item
        </a>
    </div>

    <form method="GET" class="mb-6 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-4">
        <input name="search" value="{{ request('search') }}" placeholder="Buscar por nome"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 md:col-span-2">
        <select name="type" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Todos os destinos</option>
            <option value="product" @selected(request('type') === 'product')>Produto de venda</option>
            <option value="supply" @selected(request('type') === 'supply')>Insumo técnico</option>
        </select>
        <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 text-sm text-gray-700">
            <input type="checkbox" name="low_stock" value="1" @checked(request()->has('low_stock')) class="rounded border-gray-300 text-blue-600">
            Estoque baixo
        </label>
        <div class="md:col-span-4 flex justify-end">
            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Filtrar</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Item</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Destino</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Catálogo</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Estoque</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Custo</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Preço</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $item->name }}</div>
                            @if ($item->stock_quantity <= $item->min_stock_alert)
                                <div class="text-xs font-medium text-red-600">Abaixo do mínimo de {{ number_format((float) $item->min_stock_alert, 2, ',', '.') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $item->type === 'product' ? 'Produto de venda' : 'Insumo técnico' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $item->is_for_sale ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $item->is_for_sale ? 'Visível' : 'Oculto' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">{{ number_format((float) $item->stock_quantity, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">R$ {{ number_format((float) $item->cost_price, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm text-gray-700">R$ {{ number_format((float) $item->sale_price, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('estoque.show', $item) }}" class="font-semibold text-blue-600 hover:text-blue-800">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">Nenhum item cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
@endsection
