@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $averageHours = intdiv($averageRepairSeconds, 3600);
        $averageMinutes = intdiv($averageRepairSeconds % 3600, 60);
        $averageRepairLabel = sprintf('%02d:%02d', $averageHours, $averageMinutes);
        $maxDailyRevenue = collect($dailyNetRevenueSeries)->max('value') ?: 1;
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard de performance</h1>
            <p class="text-sm text-gray-600">Métricas consolidadas de receita, produtividade, aprovação e estoque.</p>
        </div>

        <form method="GET" class="flex gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
            <input name="month" type="month" value="{{ $month->format('Y-m') }}"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Filtrar
            </button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Líquido hoje</div>
            <div class="mt-3 text-2xl font-bold text-gray-900">R$ {{ number_format($dailyNetRevenue, 2, ',', '.') }}</div>
            <p class="mt-2 text-sm text-gray-500">OS finalizadas hoje.</p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Líquido mensal</div>
            <div class="mt-3 text-2xl font-bold {{ $monthlyNetRevenue >= 0 ? 'text-green-700' : 'text-red-700' }}">
                R$ {{ number_format($monthlyNetRevenue, 2, ',', '.') }}
            </div>
            <p class="mt-2 text-sm text-gray-500">Receita cobrada menos custo dos insumos.</p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tempo médio por conserto</div>
            <div class="mt-3 text-2xl font-bold text-gray-900">{{ $averageRepairLabel }}</div>
            <p class="mt-2 text-sm text-gray-500">Baseado em OS finalizadas com time tracking.</p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Aprovação de orçamentos</div>
            <div class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($approvalRate, 1, ',', '.') }}%</div>
            <p class="mt-2 text-sm text-gray-500">{{ $approvedBudgets }} aprovados, {{ $rejectedBudgets }} recusados.</p>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-900">Faturamento líquido diário</h2>
                    <p class="text-sm text-gray-500">{{ $month->translatedFormat('F/Y') }}</p>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <div>Bruto: R$ {{ number_format($monthlyGrossRevenue, 2, ',', '.') }}</div>
                    <div>Custos: R$ {{ number_format($monthlyCosts, 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="flex h-64 items-end gap-1 border-b border-l border-gray-200 px-2 pt-4">
                @foreach ($dailyNetRevenueSeries as $point)
                    @php
                        $height = $point['value'] > 0 ? max(6, ($point['value'] / $maxDailyRevenue) * 100) : 2;
                    @endphp
                    <div class="group flex min-w-3 flex-1 flex-col items-center justify-end">
                        <div class="w-full rounded-t bg-blue-600 transition group-hover:bg-blue-700"
                            style="height: {{ $height }}%;"
                            title="{{ $point['date']->format('d/m') }} - R$ {{ number_format($point['value'], 2, ',', '.') }}">
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-3 grid grid-cols-6 gap-2 text-xs text-gray-500 md:grid-cols-12">
                @foreach ($dailyNetRevenueSeries as $point)
                    @if ($loop->first || $point['date']->day % 5 === 0 || $loop->last)
                        <span>{{ $point['date']->format('d') }}</span>
                    @endif
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Avisos de estoque crítico</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $criticalItems->count() }} item(ns) abaixo do limite mínimo.</p>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse ($criticalItems as $item)
                    <a href="{{ route('estoque.show', $item) }}" class="block px-5 py-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-semibold text-gray-900">{{ $item->name }}</div>
                                <p class="mt-1 text-sm text-red-700">
                                    Seu item está abaixo do limite mínimo.
                                </p>
                            </div>
                            <span class="rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                                {{ number_format((float) $item->stock_quantity, 2, ',', '.') }} / {{ number_format((float) $item->min_stock_alert, 2, ',', '.') }}
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="px-5 py-10 text-center text-sm text-gray-500">Nenhum estoque crítico no momento.</div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">OS abertas</div>
            <div class="mt-3 text-2xl font-bold text-gray-900">{{ $openOrdersCount }}</div>
            <a href="{{ route('tenant.kanban.index') }}" class="mt-3 inline-block text-sm font-semibold text-blue-600 hover:text-blue-700">Ver Kanban</a>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Prazos vencidos</div>
            <div class="mt-3 text-2xl font-bold {{ $overdueOrdersCount > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $overdueOrdersCount }}</div>
            <a href="{{ route('tenant.schedule.index') }}" class="mt-3 inline-block text-sm font-semibold text-blue-600 hover:text-blue-700">Abrir agenda</a>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Decisões de orçamento</div>
            <div class="mt-3 text-2xl font-bold text-gray-900">{{ $budgetDecisions }}</div>
            <p class="mt-2 text-sm text-gray-500">Aprovados, recusados e finalizados no período.</p>
        </section>
    </div>
@endsection
