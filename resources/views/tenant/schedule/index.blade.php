@extends('layouts.app')

@section('title', 'Agenda de Prazos')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Agenda e cronograma</h1>
            <p class="text-sm text-gray-600">Prazos prometidos, alertas de vencimento e recebimentos de hardware.</p>
        </div>

        <form method="GET" class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 shadow-sm sm:grid-cols-3">
            <input name="start_date" type="date" value="{{ $startDate->format('Y-m-d') }}"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <input name="end_date" type="date" value="{{ $endDate->format('Y-m-d') }}"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Filtrar
            </button>
        </form>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <section class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="text-sm font-semibold text-red-800">Prazos vencidos</div>
            <div class="mt-2 text-3xl font-bold text-red-900">{{ $overdueOrders->count() }}</div>
        </section>
        <section class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <div class="text-sm font-semibold text-amber-800">Vencem hoje</div>
            <div class="mt-2 text-3xl font-bold text-amber-900">{{ $todayOrders->count() }}</div>
        </section>
        <section class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="text-sm font-semibold text-blue-800">No período filtrado</div>
            <div class="mt-2 text-3xl font-bold text-blue-900">{{ $serviceOrders->count() }}</div>
        </section>
    </div>

    @if ($overdueOrders->isNotEmpty() || $todayOrders->isNotEmpty())
        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <section class="rounded-lg border border-red-200 bg-white shadow-sm">
                <div class="border-b border-red-100 px-4 py-3">
                    <h2 class="font-semibold text-red-900">Alertas de vencimento</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($overdueOrders->merge($todayOrders)->unique('id') as $order)
                        <a href="{{ route('ordens-servico.show', $order) }}" class="block px-4 py-3 hover:bg-gray-50">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-gray-900">OS #{{ $order->id }} - {{ $order->device_model }}</div>
                                    <div class="mt-1 text-sm text-gray-600">{{ $order->customer?->name ?? 'Cliente não informado' }}</div>
                                </div>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $order->isDeadlineOverdue() ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $order->isDeadlineOverdue() ? 'Vencida' : 'Hoje' }}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-500">Prazo: {{ $order->deadline_at?->format('d/m/Y H:i') }}</div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <h2 class="font-semibold text-gray-900">Recebimentos recentes</h2>
                <div class="mt-3 divide-y divide-gray-100">
                    @forelse ($hardwareReceipts->take(6) as $order)
                        <a href="{{ route('ordens-servico.show', $order) }}" class="block py-3 text-sm hover:bg-gray-50">
                            <div class="font-semibold text-gray-900">OS #{{ $order->id }} - {{ $order->device_model }}</div>
                            <div class="mt-1 text-gray-600">{{ $order->hardware_received_at?->format('d/m/Y H:i') }} por {{ $order->customer?->name ?? 'cliente' }}</div>
                            @if ($order->hardware_received_notes)
                                <div class="mt-1 text-gray-500">{{ $order->hardware_received_notes }}</div>
                            @endif
                        </a>
                    @empty
                        <div class="py-8 text-center text-sm text-gray-500">Nenhum recebimento registrado no período.</div>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Calendário parcial</h2>
                <span class="text-sm text-gray-500">{{ $startDate->format('d/m/Y') }} a {{ $endDate->format('d/m/Y') }}</span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($calendarDays as $day)
                    <div class="min-h-36 rounded-lg border {{ $day['date']->isToday() ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-gray-50' }} p-3">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold text-gray-900">{{ $day['date']->format('d/m') }}</div>
                            <div class="text-xs text-gray-500">{{ ucfirst($day['date']->translatedFormat('D')) }}</div>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse ($day['orders'] as $order)
                                <a href="{{ route('ordens-servico.show', $order) }}"
                                    class="block rounded-md border border-gray-200 bg-white p-2 text-xs shadow-sm hover:border-blue-300">
                                    <div class="font-semibold text-gray-900">OS #{{ $order->id }}</div>
                                    <div class="mt-1 text-gray-600">{{ $order->device_model }}</div>
                                    <div class="mt-1 {{ $order->isDeadlineOverdue() ? 'text-red-700' : 'text-gray-500' }}">
                                        {{ $order->deadline_at?->format('H:i') }} - {{ $order->kanbanColumn?->name ?? $order->status }}
                                    </div>
                                </a>
                            @empty
                                <div class="pt-4 text-center text-xs text-gray-400">Sem entregas</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-4 py-3">
                <h2 class="font-semibold text-gray-900">Ajustar prazos e recebimentos</h2>
            </div>
            <div class="max-h-[780px] divide-y divide-gray-100 overflow-y-auto">
                @forelse ($serviceOrders as $order)
                    <form method="POST" action="{{ route('tenant.schedule.service_orders.update', $order) }}" class="space-y-3 p-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <a href="{{ route('ordens-servico.show', $order) }}" class="font-semibold text-gray-900 hover:text-blue-700">
                                OS #{{ $order->id }} - {{ $order->device_model }}
                            </a>
                            <div class="mt-1 text-sm text-gray-600">{{ $order->customer?->name ?? 'Cliente não informado' }}</div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Prazo prometido</label>
                            <input name="deadline_at" type="datetime-local" value="{{ $order->deadline_at?->format('Y-m-d\TH:i') }}"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Recebimento do hardware</label>
                            <input name="hardware_received_at" type="datetime-local" value="{{ $order->hardware_received_at?->format('Y-m-d\TH:i') }}"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="mark_hardware_received" value="1" class="rounded border-gray-300 text-blue-600">
                            Marcar recebimento agora
                        </label>

                        <textarea name="hardware_received_notes" rows="2" placeholder="Observações do recebimento"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $order->hardware_received_notes }}</textarea>

                        <button class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                            Salvar agenda
                        </button>
                    </form>
                @empty
                    <div class="p-8 text-center text-sm text-gray-500">Nenhuma OS com prazo no período.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
