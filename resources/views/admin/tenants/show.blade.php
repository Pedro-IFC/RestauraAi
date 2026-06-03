@extends('layouts.admin')

@section('title', $tenant->name)

@section('content')
    @php
        $statusClasses = [
            'active' => 'bg-green-100 text-green-700',
            'trial' => 'bg-blue-100 text-blue-700',
            'suspended' => 'bg-red-100 text-red-700',
            'canceled' => 'bg-gray-200 text-gray-700',
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <a href="{{ route('admin.tenants.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Voltar</a>
            <h1 class="mt-2 text-3xl font-bold text-gray-950">{{ $tenant->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">/{{ $tenant->slug }} · {{ $tenant->document }}</p>
        </div>
        <span class="w-fit rounded-full px-3 py-1 text-sm font-semibold {{ $statusClasses[$tenant->status] ?? 'bg-gray-100 text-gray-700' }}">
            {{ $tenant->statusLabel() }}
        </span>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Plano</div>
            <div class="mt-2 text-lg font-bold text-gray-900">{{ $tenant->plan?->name ?? 'Sem plano' }}</div>
        </section>
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Usuários</div>
            <div class="mt-2 text-lg font-bold text-gray-900">{{ $tenant->users_count }}</div>
        </section>
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">OS</div>
            <div class="mt-2 text-lg font-bold text-gray-900">{{ $tenant->service_orders_count }}</div>
        </section>
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Itens</div>
            <div class="mt-2 text-lg font-bold text-gray-900">{{ $tenant->items_count }}</div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">Cobrança e ciclo de vida</h2>
            <form method="POST" action="{{ route('admin.subscriptions.billing', $tenant) }}" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status da assinatura</label>
                    <select name="status" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="active" @selected($tenant->status === 'active')>Ativa</option>
                        <option value="trial" @selected($tenant->status === 'trial')>Em período de testes</option>
                        <option value="suspended" @selected($tenant->status === 'suspended')>Inadimplente</option>
                        <option value="canceled" @selected($tenant->status === 'canceled')>Cancelada</option>
                    </select>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Atraso desde</label>
                        <input name="payment_overdue_since" type="date" value="{{ $tenant->payment_overdue_since?->format('Y-m-d') }}"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Carência automática (dias)</label>
                        <input name="payment_grace_days" type="number" min="0" max="90" value="{{ $tenant->payment_grace_days ?? 7 }}"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Notas internas</label>
                    <textarea name="subscription_notes" rows="4" class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $tenant->subscription_notes }}</textarea>
                </div>

                <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Salvar cobrança
                </button>
            </form>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-gray-900">Ações rápidas</h2>
            <div class="mt-5 space-y-3">
                <form method="POST" action="{{ route('admin.subscriptions.suspend', $tenant) }}">
                    @csrf
                    <button class="w-full rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Suspender por inadimplência
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.subscriptions.reactivate', $tenant) }}">
                    @csrf
                    <button class="w-full rounded-lg border border-green-300 px-4 py-2 text-sm font-semibold text-green-700 hover:bg-green-50">
                        Reativar assinatura
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.subscriptions.cancel', $tenant) }}">
                    @csrf
                    <button class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Cancelar assinatura
                    </button>
                </form>
            </div>

            <form method="POST" action="{{ route('admin.subscriptions.trial', $tenant) }}" class="mt-6 border-t border-gray-100 pt-5">
                @csrf
                @method('PATCH')
                <label class="block text-sm font-medium text-gray-700">Definir novo trial em dias</label>
                <div class="mt-2 flex gap-2">
                    <input name="trial_days" type="number" min="0" max="365" value="7"
                        class="min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Aplicar
                    </button>
                </div>
            </form>

            <div class="mt-6 rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                <div><span class="font-semibold text-gray-800">Trial termina:</span> {{ $tenant->trial_ends_at?->format('d/m/Y H:i') ?? 'Não definido' }}</div>
                <div><span class="font-semibold text-gray-800">Suspenso em:</span> {{ $tenant->suspended_at?->format('d/m/Y H:i') ?? 'Não definido' }}</div>
                <div><span class="font-semibold text-gray-800">Cancelado em:</span> {{ $tenant->canceled_at?->format('d/m/Y H:i') ?? 'Não definido' }}</div>
            </div>
        </section>
    </div>
@endsection
