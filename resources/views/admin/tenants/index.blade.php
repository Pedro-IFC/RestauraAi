@extends('layouts.admin')

@section('title', 'Assistências')

@section('content')
    @php
        $statusClasses = [
            'active' => 'bg-green-100 text-green-700',
            'trial' => 'bg-blue-100 text-blue-700',
            'suspended' => 'bg-red-100 text-red-700',
            'canceled' => 'bg-gray-200 text-gray-700',
        ];
    @endphp

    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Superadmin</p>
                <h1 class="text-3xl font-bold text-gray-950">Assistências e assinaturas</h1>
                <p class="mt-1 text-sm text-gray-600">Acompanhe status, trial, inadimplência e ciclo de vida das assinaturas.</p>
            </div>
            <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Nova assistência
            </a>
        </div>

        <form method="GET" class="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-4">
            <input name="search" value="{{ request('search') }}" placeholder="Buscar por nome, slug ou documento"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 md:col-span-2">
            <select name="status" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos os status</option>
                <option value="active" @selected(request('status') === 'active')>Ativa</option>
                <option value="trial" @selected(request('status') === 'trial')>Trial</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Inadimplente</option>
                <option value="canceled" @selected(request('status') === 'canceled')>Cancelada</option>
            </select>
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Filtrar
            </button>
        </form>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assistência</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Plano</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Cobrança</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Uso</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tenants as $tenant)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-950">{{ $tenant->name }}</div>
                                <div class="text-sm text-gray-500">/{{ $tenant->slug }} · {{ $tenant->document }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">{{ $tenant->plan?->name ?? 'Sem plano' }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$tenant->status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $tenant->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-700">
                                @if ($tenant->payment_overdue_since)
                                    <div>Em atraso desde {{ $tenant->payment_overdue_since->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">Carência: {{ $tenant->payment_grace_days }} dia(s)</div>
                                @else
                                    <span class="text-gray-400">Sem atraso registrado</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right text-sm text-gray-700">
                                <div>{{ $tenant->service_orders_count }} OS</div>
                                <div>{{ $tenant->items_count }} itens</div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.tenants.show', $tenant) }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                    Gerenciar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-500">Nenhuma assistência encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tenants->links() }}
    </div>
@endsection
