@extends('layouts.app')

@section('title', 'OS #' . $serviceOrder->id)

@section('content')
    @php
        $profit = (float) $serviceOrder->total_price - (float) $serviceOrder->total_cost;
        $trackedSeconds = $serviceOrder->trackedSeconds();
        $trackedHours = intdiv($trackedSeconds, 3600);
        $trackedMinutes = intdiv($trackedSeconds % 3600, 60);
        $trackedLabel = sprintf('%02d:%02d', $trackedHours, $trackedMinutes);
        $currentUserRunningEntry = $serviceOrder->runningTimeEntryForUser(auth()->id());
    @endphp

    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">OS #{{ $serviceOrder->id }}</h1>
            <p class="text-sm text-gray-600">{{ $serviceOrder->customer?->name }} - {{ $serviceOrder->device_model }}</p>
        </div>
        <span class="w-fit rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">{{ $serviceOrder->status }}</span>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        @if (auth()->user()->tenant?->hasFeature('time_tracking'))
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Preço cobrado</div>
            <div class="mt-2 text-xl font-bold text-gray-900">R$ {{ number_format((float) $serviceOrder->total_price, 2, ',', '.') }}</div>
        </div>
        @endif
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Custo de insumos</div>
            <div class="mt-2 text-xl font-bold text-gray-900">R$ {{ number_format((float) $serviceOrder->total_cost, 2, ',', '.') }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Margem real</div>
            <div class="mt-2 text-xl font-bold {{ $profit >= 0 ? 'text-green-700' : 'text-red-700' }}">R$ {{ number_format($profit, 2, ',', '.') }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tempo técnico</div>
                    <div class="mt-2 text-xl font-bold text-gray-900">{{ $trackedLabel }}</div>
                </div>
                <form method="POST" action="{{ $currentUserRunningEntry ? route('tenant.tracking.stop', $serviceOrder) : route('tenant.tracking.start', $serviceOrder) }}">
                    @csrf
                    <button class="rounded-full px-4 py-2 text-xs font-bold {{ $currentUserRunningEntry ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700' }}">
                        {{ $currentUserRunningEntry ? 'Pause' : 'Play' }}
                    </button>
                </form>
            </div>
            @if ($serviceOrder->timeEntries->firstWhere('ended_at', null))
                <p class="mt-2 text-xs text-green-700">Cronômetro em andamento.</p>
            @else
                <p class="mt-2 text-xs text-gray-500">Cronômetro pausado.</p>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Início planejado</div>
            <div class="mt-2 text-lg font-bold text-gray-900">
                {{ $serviceOrder->planned_start_at?->format('d/m/Y H:i') ?? 'Sem início definido' }}
            </div>
            @if ($serviceOrder->schedule_notes)
                <p class="mt-1 text-sm text-gray-600">{{ $serviceOrder->schedule_notes }}</p>
            @endif
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Prazo prometido</div>
            <div class="mt-2 text-lg font-bold {{ $serviceOrder->isDeadlineOverdue() ? 'text-red-700' : 'text-gray-900' }}">
                {{ $serviceOrder->deadline_at?->format('d/m/Y H:i') ?? 'Sem prazo definido' }}
            </div>
            @if ($serviceOrder->isDeadlineOverdue())
                <p class="mt-1 text-sm text-red-600">Prazo vencido.</p>
            @elseif ($serviceOrder->isDeadlineDueToday())
                <p class="mt-1 text-sm text-amber-600">Entrega prometida para hoje.</p>
            @endif
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recebimento do hardware</div>
            <div class="mt-2 text-lg font-bold text-gray-900">
                {{ $serviceOrder->hardware_received_at?->format('d/m/Y H:i') ?? 'Não registrado' }}
            </div>
            @if ($serviceOrder->hardware_received_notes)
                <p class="mt-1 text-sm text-gray-600">{{ $serviceOrder->hardware_received_notes }}</p>
            @endif
            <a href="{{ route('tenant.schedule.index') }}" class="mt-3 inline-block text-sm font-semibold text-blue-600 hover:text-blue-700">
                Atualizar na agenda
            </a>
        </section>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Orçamento do cliente</h2>
            <form method="POST" action="{{ route('ordens-servico.update', $serviceOrder) }}" class="mt-4 grid gap-4 md:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <label for="total_price" class="block text-sm font-medium text-gray-700">Valor cobrado</label>
                    <input id="total_price" name="total_price" type="number" min="0" step="0.01" value="{{ old('total_price', $serviceOrder->total_price) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="pending" @selected($serviceOrder->status === 'pending')>Pendente</option>
                        <option value="budgeting" @selected($serviceOrder->status === 'budgeting')>Orçamento enviado</option>
                        <option value="approved" @selected($serviceOrder->status === 'approved')>Aprovado</option>
                        <option value="rejected" @selected($serviceOrder->status === 'rejected')>Recusado</option>
                        <option value="finished" @selected($serviceOrder->status === 'finished')>Finalizado</option>
                    </select>
                </div>
                <div>
                    <label for="planned_start_at" class="block text-sm font-medium text-gray-700">Início planejado</label>
                    <input id="planned_start_at" name="planned_start_at" type="datetime-local" value="{{ old('planned_start_at', $serviceOrder->planned_start_at?->format('Y-m-d\TH:i')) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="deadline_at" class="block text-sm font-medium text-gray-700">Prazo prometido</label>
                    <input id="deadline_at" name="deadline_at" type="datetime-local" value="{{ old('deadline_at', $serviceOrder->deadline_at?->format('Y-m-d\TH:i')) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="hardware_received_at" class="block text-sm font-medium text-gray-700">Recebimento do hardware</label>
                    <input id="hardware_received_at" name="hardware_received_at" type="datetime-local" value="{{ old('hardware_received_at', $serviceOrder->hardware_received_at?->format('Y-m-d\TH:i')) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="next_kanban_column_id" class="block text-sm font-medium text-gray-700">Encaminhar após recebimento</label>
                    <select id="next_kanban_column_id" name="next_kanban_column_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Próxima etapa do Kanban</option>
                        @foreach ($kanbanColumns as $column)
                            <option value="{{ $column->id }}" @selected((string) old('next_kanban_column_id') === (string) $column->id)>{{ $column->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="hardware_received_notes" class="block text-sm font-medium text-gray-700">Observações do recebimento</label>
                    <input id="hardware_received_notes" name="hardware_received_notes" value="{{ old('hardware_received_notes', $serviceOrder->hardware_received_notes) }}"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label for="schedule_notes" class="block text-sm font-medium text-gray-700">Notas de planejamento</label>
                    <textarea id="schedule_notes" name="schedule_notes" rows="3"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('schedule_notes', $serviceOrder->schedule_notes) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Atualizar OS
                    </button>
                </div>
            </form>
            @if ($serviceOrder->budget_decided_at)
                <p class="mt-3 text-sm text-gray-600">Cliente respondeu em {{ $serviceOrder->budget_decided_at->format('d/m/Y H:i') }}.</p>
            @endif
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Anexos do cliente</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @forelse ($serviceOrder->attachments ?? [] as $attachment)
                    <a href="{{ asset('storage/'.$attachment) }}" target="_blank" rel="noopener noreferrer"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                        {{ basename($attachment) }}
                    </a>
                @empty
                    <p class="text-sm text-gray-500">Nenhuma foto ou vídeo enviado pelo cliente.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
            <h2 class="font-semibold text-gray-900">Itens vinculados</h2>
            <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Item</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Qtd.</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Custo</th>
                            <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Cobrado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($serviceOrder->items as $item)
                            <tr>
                                <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $item->name }}</td>
                                <td class="px-3 py-2 text-right text-sm text-gray-700">{{ number_format((float) $item->pivot->quantity, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right text-sm text-gray-700">R$ {{ number_format((float) $item->pivot->quantity * (float) $item->pivot->unit_cost, 2, ',', '.') }}</td>
                                <td class="px-3 py-2 text-right text-sm text-gray-700">R$ {{ number_format((float) $item->pivot->quantity * (float) $item->pivot->unit_price, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-8 text-center text-sm text-gray-500">Nenhum insumo ou produto vinculado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if (auth()->user()->tenant?->hasFeature('inventory'))
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-gray-900">Adicionar uso de estoque</h2>
            <form method="POST" action="{{ route('tenant.os.attach_items', $serviceOrder) }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="item_id" class="block text-sm font-medium text-gray-700">Item usado</label>
                    <select id="item_id" name="item_id" required class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        @foreach ($availableItems as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->type === 'supply' ? 'Insumo' : 'Produto' }} - {{ $item->name }} ({{ number_format((float) $item->stock_quantity, 2, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantidade usada</label>
                    <input id="quantity" name="quantity" type="number" min="0.01" step="0.01" value="1" required
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Baixar estoque
                </button>
            </form>
        </section>
        @endif
    </div>

    @if (auth()->user()->tenant?->hasFeature('time_tracking'))
    <section class="mt-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-gray-900">Auditoria de tempo</h2>

        <form method="POST" action="{{ route('tenant.tracking.manual', $serviceOrder) }}" class="mt-4 grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 md:grid-cols-[1fr_120px_120px_auto] md:items-end">
            @csrf
            <div>
                <label for="manual_started_at" class="block text-sm font-medium text-gray-700">Início do trabalho</label>
                <input id="manual_started_at" name="started_at" type="datetime-local" value="{{ old('started_at', now()->format('Y-m-d\TH:i')) }}" required
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="manual_hours" class="block text-sm font-medium text-gray-700">Horas</label>
                <input id="manual_hours" name="hours" type="number" min="0" max="999" value="{{ old('hours', 0) }}"
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="manual_minutes" class="block text-sm font-medium text-gray-700">Minutos</label>
                <input id="manual_minutes" name="minutes" type="number" min="0" max="59" value="{{ old('minutes', 30) }}"
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                Adicionar tempo
            </button>
        </form>

        <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Técnico</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Início</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Fim</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Duração</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($serviceOrder->timeEntries->sortByDesc('started_at') as $entry)
                        @php
                            $entrySeconds = $entry->ended_at ? (int) $entry->duration_seconds : (int) $entry->started_at->diffInSeconds(now());
                            $entryHours = intdiv($entrySeconds, 3600);
                            $entryMinutes = intdiv($entrySeconds % 3600, 60);
                            $entryLabel = sprintf('%02d:%02d', $entryHours, $entryMinutes);
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $entry->user?->name ?? 'Técnico' }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ $entry->started_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 text-sm text-gray-700">{{ $entry->ended_at?->format('d/m/Y H:i') ?? 'Em andamento' }}</td>
                            <td class="px-3 py-2 text-right text-sm text-gray-700">{{ $entryLabel }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-8 text-center text-sm text-gray-500">Nenhum tempo registrado nesta OS.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif
@endsection
