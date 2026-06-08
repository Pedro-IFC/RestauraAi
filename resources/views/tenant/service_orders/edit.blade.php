@extends('layouts.app')

@section('title', 'Editar OS #' . $serviceOrder->id)

@section('content')
    <div class="mb-6">
        <a href="{{ route('ordens-servico.show', $serviceOrder) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Voltar para OS</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Editar OS #{{ $serviceOrder->id }}</h1>
        <p class="text-sm text-gray-600">Atualize os dados técnicos, etapa e planejamento da agenda.</p>
    </div>

    <form method="POST" action="{{ route('ordens-servico.update', $serviceOrder) }}" class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        @csrf
        @method('PUT')

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="customer_id" class="block text-sm font-medium text-gray-700">Cliente</label>
                <select id="customer_id" name="customer_id" disabled class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50 text-sm shadow-sm">
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected($serviceOrder->customer_id === $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="kanban_column_id" class="block text-sm font-medium text-gray-700">Etapa</label>
                <select id="kanban_column_id" disabled class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50 text-sm shadow-sm">
                    @foreach ($kanbanColumns as $column)
                        <option value="{{ $column->id }}" @selected($serviceOrder->kanban_column_id === $column->id)>{{ $column->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="device_model" class="block text-sm font-medium text-gray-700">Equipamento/modelo</label>
                <input id="device_model" name="device_model" value="{{ old('device_model', $serviceOrder->device_model) }}" required
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select id="status" name="status" required class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                <label for="total_price" class="block text-sm font-medium text-gray-700">Valor cobrado</label>
                <input id="total_price" name="total_price" type="number" min="0" step="0.01" value="{{ old('total_price', $serviceOrder->total_price) }}"
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2">
                <label for="defect_symptoms" class="block text-sm font-medium text-gray-700">Sintomas/defeito relatado</label>
                <textarea id="defect_symptoms" name="defect_symptoms" rows="4" required
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('defect_symptoms', $serviceOrder->defect_symptoms) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label for="hardware_received_notes" class="block text-sm font-medium text-gray-700">Observações do recebimento</label>
                <textarea id="hardware_received_notes" name="hardware_received_notes" rows="2"
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('hardware_received_notes', $serviceOrder->hardware_received_notes) }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label for="schedule_notes" class="block text-sm font-medium text-gray-700">Notas de planejamento</label>
                <textarea id="schedule_notes" name="schedule_notes" rows="3"
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('schedule_notes', $serviceOrder->schedule_notes) }}</textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Salvar OS
            </button>
        </div>
    </form>
@endsection
