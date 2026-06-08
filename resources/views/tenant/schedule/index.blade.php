@extends('layouts.app')

@section('title', 'Agenda')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Agenda e planejamento</h1>
            <p class="text-sm text-gray-600">Planejamento de OS, prazos, recebimentos, eventos e lembretes da assistência.</p>
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

    <div class="mb-6 grid gap-4 md:grid-cols-4">
        <section class="rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="text-sm font-semibold text-red-800">Prazos vencidos</div>
            <div class="mt-2 text-3xl font-bold text-red-900">{{ $overdueOrders->count() }}</div>
        </section>
        <section class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <div class="text-sm font-semibold text-amber-800">Vencem hoje</div>
            <div class="mt-2 text-3xl font-bold text-amber-900">{{ $todayOrders->count() }}</div>
        </section>
        <section class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="text-sm font-semibold text-blue-800">OS no período</div>
            <div class="mt-2 text-3xl font-bold text-blue-900">{{ $serviceOrders->count() }}</div>
        </section>
        <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
            <div class="text-sm font-semibold text-emerald-800">Eventos no período</div>
            <div class="mt-2 text-3xl font-bold text-emerald-900">{{ $events->count() }}</div>
        </section>
    </div>

    @if ($overdueOrders->isNotEmpty() || $todayOrders->isNotEmpty() || $dueReminders->isNotEmpty())
        <div class="mb-6 grid gap-4 lg:grid-cols-2">
            <section class="rounded-lg border border-red-200 bg-white shadow-sm">
                <div class="border-b border-red-100 px-4 py-3">
                    <h2 class="font-semibold text-red-900">Alertas de OS</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($overdueOrders->merge($todayOrders)->unique('id') as $order)
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
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-500">Nenhum prazo crítico.</div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-lg border border-amber-200 bg-white shadow-sm">
                <div class="border-b border-amber-100 px-4 py-3">
                    <h2 class="font-semibold text-amber-900">Lembretes pendentes</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($dueReminders as $event)
                        <div class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $event->title }}</div>
                                    <div class="mt-1 text-sm text-gray-600">
                                        {{ $event->typeLabel() }} para {{ $event->remind_at?->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('tenant.schedule.events.update', $event) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="complete" value="1">
                                    <button class="rounded-lg border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                        Concluir
                                    </button>
                                </form>
                            </div>
                            @if ($event->serviceOrder)
                                <a href="{{ route('ordens-servico.show', $event->serviceOrder) }}" class="mt-2 inline-block text-sm font-semibold text-blue-600 hover:text-blue-700">
                                    OS #{{ $event->serviceOrder->id }}
                                </a>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-gray-500">Nenhum lembrete pendente.</div>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-semibold text-gray-900">Calendário do período</h2>
                <span class="text-sm text-gray-500">{{ $startDate->format('d/m/Y') }} a {{ $endDate->format('d/m/Y') }}</span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($calendarDays as $day)
                    <div class="min-h-40 rounded-lg border {{ $day['date']->isToday() ? 'border-blue-300 bg-blue-50' : 'border-gray-200 bg-gray-50' }} p-3">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold text-gray-900">{{ $day['date']->format('d/m') }}</div>
                            <div class="text-xs text-gray-500">{{ ucfirst($day['date']->translatedFormat('D')) }}</div>
                        </div>

                        <div class="mt-3 space-y-2">
                            @foreach ($day['orders'] as $order)
                                <a href="{{ route('ordens-servico.show', $order) }}"
                                    class="block rounded-md border border-blue-100 bg-white p-2 text-xs shadow-sm hover:border-blue-300">
                                    <div class="font-semibold text-gray-900">OS #{{ $order->id }}</div>
                                    <div class="mt-1 text-gray-600">{{ $order->device_model }}</div>
                                    @if ($order->planned_start_at && $order->planned_start_at->isSameDay($day['date']))
                                        <div class="mt-1 text-blue-700">{{ $order->planned_start_at->format('H:i') }} - Início planejado</div>
                                    @endif
                                    @if ($order->deadline_at && $order->deadline_at->isSameDay($day['date']))
                                        <div class="mt-1 {{ $order->isDeadlineOverdue() ? 'text-red-700' : 'text-gray-500' }}">
                                            {{ $order->deadline_at->format('H:i') }} - Prazo prometido
                                        </div>
                                    @endif
                                </a>
                            @endforeach

                            @foreach ($day['events'] as $event)
                                <div class="rounded-md border border-emerald-100 bg-white p-2 text-xs shadow-sm">
                                    <div class="font-semibold text-gray-900">{{ $event->title }}</div>
                                    <div class="mt-1 text-emerald-700">{{ $event->starts_at->format('H:i') }} - {{ $event->typeLabel() }}</div>
                                    @if ($event->serviceOrder)
                                        <a href="{{ route('ordens-servico.show', $event->serviceOrder) }}" class="mt-1 inline-block font-semibold text-blue-600 hover:text-blue-700">
                                            OS #{{ $event->serviceOrder->id }}
                                        </a>
                                    @endif
                                </div>
                            @endforeach

                            @if ($day['orders']->isEmpty() && $day['events']->isEmpty())
                                <div class="pt-4 text-center text-xs text-gray-400">Sem agenda</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <aside class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Cadastrar evento ou lembrete</h2>
                <form method="POST" action="{{ route('tenant.schedule.events.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Título</label>
                        <input id="title" name="title" value="{{ old('title') }}" required
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Tipo</label>
                            <select id="type" name="type" required class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach ($eventTypeLabels as $type => $label)
                                    <option value="{{ $type }}" @selected(old('type') === $type)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="service_order_id" class="block text-sm font-medium text-gray-700">OS vinculada</label>
                            <select id="service_order_id" name="service_order_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Sem vínculo</option>
                                @foreach ($availableServiceOrders as $order)
                                    <option value="{{ $order->id }}" @selected((string) old('service_order_id') === (string) $order->id)>
                                        OS #{{ $order->id }} - {{ $order->device_model }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="starts_at" class="block text-sm font-medium text-gray-700">Início</label>
                            <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" required
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="ends_at" class="block text-sm font-medium text-gray-700">Fim</label>
                            <input id="ends_at" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label for="remind_at" class="block text-sm font-medium text-gray-700">Lembrar em</label>
                        <input id="remind_at" name="remind_at" type="datetime-local" value="{{ old('remind_at') }}"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700">Local</label>
                        <input id="location" name="location" value="{{ old('location') }}"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    </div>

                    <button class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Salvar na agenda
                    </button>
                </form>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-4 py-3">
                    <h2 class="font-semibold text-gray-900">Eventos do período</h2>
                </div>
                <div class="max-h-[420px] divide-y divide-gray-100 overflow-y-auto">
                    @forelse ($events as $event)
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $event->title }}</div>
                                    <div class="mt-1 text-sm text-gray-600">
                                        {{ $event->typeLabel() }} em {{ $event->starts_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('tenant.schedule.events.destroy', $event) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-semibold text-red-600 hover:text-red-700">Remover</button>
                                </form>
                            </div>
                            @if ($event->notes)
                                <p class="mt-2 text-sm text-gray-600">{{ $event->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500">Nenhum evento no período.</div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    <section class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="font-semibold text-gray-900">Planejar ordens de serviço</h2>
        </div>
        <div class="grid divide-y divide-gray-100 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
            @forelse ($availableServiceOrders as $order)
                <form method="POST" action="{{ route('tenant.schedule.service_orders.update', $order) }}" class="space-y-3 p-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <a href="{{ route('ordens-servico.show', $order) }}" class="font-semibold text-gray-900 hover:text-blue-700">
                            OS #{{ $order->id }} - {{ $order->device_model }}
                        </a>
                        <div class="mt-1 text-sm text-gray-600">{{ $order->customer?->name ?? 'Cliente não informado' }}</div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Início planejado</label>
                            <input name="planned_start_at" type="datetime-local" value="{{ $order->planned_start_at?->format('Y-m-d\TH:i') }}"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Prazo prometido</label>
                            <input name="deadline_at" type="datetime-local" value="{{ $order->deadline_at?->format('Y-m-d\TH:i') }}"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
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

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Encaminhar para etapa</label>
                        <select name="next_kanban_column_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Próxima etapa do Kanban</option>
                            @foreach ($kanbanColumns as $column)
                                <option value="{{ $column->id }}">{{ $column->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <textarea name="hardware_received_notes" rows="2" placeholder="Observações do recebimento"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $order->hardware_received_notes }}</textarea>

                    <textarea name="schedule_notes" rows="2" placeholder="Notas de planejamento, etapa técnica ou dependências"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ $order->schedule_notes }}</textarea>

                    <button class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Salvar planejamento
                    </button>
                </form>
            @empty
                <div class="p-8 text-center text-sm text-gray-500 lg:col-span-2">Nenhuma OS aberta para planejamento.</div>
            @endforelse
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h2 class="font-semibold text-gray-900">Recebimentos registrados</h2>
        <div class="mt-3 divide-y divide-gray-100">
            @forelse ($hardwareReceipts->take(8) as $order)
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
@endsection
