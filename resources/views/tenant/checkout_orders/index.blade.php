@extends('layouts.app')

@section('title', 'Pedidos do Catalogo')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Pedidos do catalogo</h1>
            <p class="text-sm text-gray-600">Auditoria e gestao dos pedidos de produtos vendidos no microssite.</p>
        </div>
    </div>

    <form method="GET" class="mb-6 grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-[1fr_auto]">
        <select name="state" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Todos os estados</option>
            @foreach ($stateLabels as $state => $label)
                <option value="{{ $state }}" @selected(request('state') === $state)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Filtrar</button>
    </form>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Pedido</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Estado atual</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Ultima alteracao</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Total</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    @php($lastHistory = $order->statusHistories->first())
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">#{{ $order->id }}</div>
                            <div class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $order->customer?->name ?? 'Cliente nao vinculado' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="rounded-full bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-700">
                                {{ $order->auditStateLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            @if ($lastHistory)
                                {{ $lastHistory->created_at->format('d/m/Y H:i') }}
                            @else
                                Sem historico
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                            R$ {{ number_format((float) $order->total_amount, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('tenant.checkout-orders.show', $order) }}" class="font-semibold text-blue-600 hover:text-blue-800">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">Nenhum pedido do catalogo encontrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
@endsection
