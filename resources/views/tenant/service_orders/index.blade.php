@extends('layouts.app')

@section('title', 'Ordens de Serviço')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Ordens de serviço</h1>
            <p class="text-sm text-gray-600">Cadastro, acompanhamento e planejamento das OS da assistência.</p>
        </div>
        <a href="{{ route('ordens-servico.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Nova OS
        </a>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">OS</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Etapa</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Agenda</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($serviceOrders as $order)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900">OS #{{ $order->id }}</div>
                            <div class="text-sm text-gray-600">{{ $order->device_model }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $order->customer?->name ?? 'Cliente não informado' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $order->kanbanColumn?->name ?? $order->status }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">
                            <div>Início: {{ $order->planned_start_at?->format('d/m/Y H:i') ?? 'sem data' }}</div>
                            <div>Prazo: {{ $order->deadline_at?->format('d/m/Y H:i') ?? 'sem data' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('ordens-servico.show', $order) }}" class="font-semibold text-blue-600 hover:text-blue-800">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">Nenhuma OS cadastrada.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $serviceOrders->links() }}
    </div>
@endsection
