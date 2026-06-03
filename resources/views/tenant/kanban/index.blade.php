@extends('layouts.app')

@section('title', 'Kanban Operacional')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Kanban Operacional</h1>
            <p class="text-sm text-gray-600">Acompanhe os chamados do link público e mova cada OS pelo fluxo da bancada.</p>
        </div>

        <form method="POST" action="{{ route('tenant.kanban.columns.store') }}" class="flex w-full gap-2 lg:w-auto">
            @csrf
            <input name="name" type="text" placeholder="Nova coluna" required
                class="min-w-0 flex-1 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 lg:w-56">
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                Adicionar
            </button>
        </form>
    </div>

    <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        Arraste um cartão para outra coluna ou use o seletor dentro do cartão. A posição é salva no painel da assistência.
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4" data-kanban-board>
        @foreach ($columns as $column)
            <section class="flex max-h-[72vh] w-80 shrink-0 flex-col rounded-lg border border-gray-200 bg-gray-50"
                data-kanban-column="{{ $column->id }}">
                <div class="border-b border-gray-200 bg-white p-3">
                    <form method="POST" action="{{ route('tenant.kanban.columns.update', $column) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <input name="name" value="{{ $column->name }}" required
                            class="min-w-0 flex-1 rounded-md border-gray-300 text-sm font-semibold text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <input name="order_index" type="number" min="1" value="{{ $column->order_index }}" title="Ordem"
                            class="w-16 rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <button class="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                            OK
                        </button>
                    </form>

                    <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ $column->serviceOrders->count() }} chamado(s)</span>
                        <form method="POST" action="{{ route('tenant.kanban.columns.destroy', $column) }}">
                            @csrf
                            @method('DELETE')
                            <button class="font-semibold text-red-600 hover:text-red-700">Remover</button>
                        </form>
                    </div>
                </div>

                <div class="flex-1 space-y-3 overflow-y-auto p-3" data-kanban-dropzone>
                    @forelse ($column->serviceOrders as $order)
                        @php
                            $trackedSeconds = $order->trackedSeconds();
                            $trackedHours = intdiv($trackedSeconds, 3600);
                            $trackedMinutes = intdiv($trackedSeconds % 3600, 60);
                            $trackedLabel = sprintf('%02d:%02d', $trackedHours, $trackedMinutes);
                            $runningEntry = $order->timeEntries->firstWhere('ended_at', null);
                            $currentUserRunningEntry = $order->runningTimeEntryForUser(auth()->id());
                        @endphp
                        <article class="cursor-grab rounded-lg border border-gray-200 bg-white p-4 shadow-sm active:cursor-grabbing"
                            draggable="true"
                            data-service-order="{{ $order->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <a href="{{ route('ordens-servico.show', $order) }}" class="font-semibold text-gray-900 hover:text-blue-700">
                                        OS #{{ $order->id }}
                                    </a>
                                    <p class="mt-1 text-sm text-gray-600">{{ $order->device_model }}</p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">{{ $order->status }}</span>
                            </div>

                            <div class="mt-3 text-sm text-gray-600">
                                <div class="font-medium text-gray-800">{{ $order->customer?->name ?? 'Cliente não informado' }}</div>
                                <div class="mt-1 line-clamp-2">{{ $order->defect_symptoms }}</div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-gray-500">
                                <div>
                                    <span class="block font-semibold text-gray-700">Entrada</span>
                                    {{ $order->created_at?->format('d/m/Y') }}
                                </div>
                                <div>
                                    <span class="block font-semibold text-gray-700">Prazo</span>
                                    {{ $order->deadline_at?->format('d/m/Y') ?? 'Sem prazo' }}
                                </div>
                            </div>

                            @if (auth()->user()->tenant?->hasFeature('time_tracking'))
                            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Tempo</span>
                                        <span class="text-lg font-bold text-gray-900">{{ $trackedLabel }}</span>
                                    </div>
                                    <form method="POST" action="{{ $currentUserRunningEntry ? route('tenant.tracking.stop', $order) : route('tenant.tracking.start', $order) }}">
                                        @csrf
                                        <button class="rounded-full px-4 py-2 text-xs font-bold {{ $currentUserRunningEntry ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700' }}">
                                            {{ $currentUserRunningEntry ? 'Pause' : 'Play' }}
                                        </button>
                                    </form>
                                </div>
                                @if ($runningEntry)
                                    <p class="mt-2 text-xs text-green-700">
                                        Em andamento por {{ $runningEntry->user?->name ?? 'técnico' }}
                                    </p>
                                @else
                                    <p class="mt-2 text-xs text-gray-500">Cronômetro pausado</p>
                                @endif
                            </div>
                            @endif

                            <form method="POST" action="{{ route('tenant.kanban.move') }}" class="mt-4 flex gap-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="service_order_id" value="{{ $order->id }}">
                                <select name="kanban_column_id"
                                    class="min-w-0 flex-1 rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach ($columns as $targetColumn)
                                        <option value="{{ $targetColumn->id }}" @selected($targetColumn->id === $column->id)>
                                            {{ $targetColumn->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">
                                    Mover
                                </button>
                            </form>
                        </article>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                            Sem chamados nesta etapa.
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            let draggedCard = null;

            document.querySelectorAll('[data-service-order]').forEach((card) => {
                card.addEventListener('dragstart', () => {
                    draggedCard = card;
                    card.classList.add('opacity-60');
                });

                card.addEventListener('dragend', () => {
                    card.classList.remove('opacity-60');
                    draggedCard = null;
                });
            });

            document.querySelectorAll('[data-kanban-column]').forEach((column) => {
                column.addEventListener('dragover', (event) => {
                    event.preventDefault();
                });

                column.addEventListener('drop', async (event) => {
                    event.preventDefault();

                    if (!draggedCard) {
                        return;
                    }

                    const dropzone = column.querySelector('[data-kanban-dropzone]');
                    const serviceOrderId = draggedCard.dataset.serviceOrder;
                    const columnId = column.dataset.kanbanColumn;

                    dropzone.appendChild(draggedCard);

                    const cards = [...dropzone.querySelectorAll('[data-service-order]')];
                    const position = cards.findIndex((card) => card.dataset.serviceOrder === serviceOrderId) + 1;

                    await fetch('{{ route('tenant.kanban.move') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            _method: 'PATCH',
                            service_order_id: serviceOrderId,
                            kanban_column_id: columnId,
                            position,
                        }),
                    });
                });
            });
        })();
    </script>
@endpush
