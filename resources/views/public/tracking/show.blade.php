@extends('layouts.app')

@section('title', 'Status do chamado')

@section('content')
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Status do chamado</h1>
        </div>
        <a href="{{ route('public.tracking.index', $tenant->slug) }}" class="w-fit rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
            Nova consulta
        </a>
    </div>

    <div class="space-y-4">
        @foreach ($orders as $order)
            @php
                $canDecideBudget = (float) $order->total_price > 0 && ! in_array($order->status, ['approved', 'rejected', 'finished'], true);
                $statusLabel = [
                    'pending' => 'Pendente',
                    'budgeting' => 'Orçamento',
                    'approved' => 'Orçamento aprovado',
                    'rejected' => 'Orçamento recusado',
                    'finished' => 'Finalizado',
                ][$order->status] ?? $order->status;
            @endphp

            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Chamado #{{ $order->id }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $order->device_model }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                            {{ $order->kanbanColumn?->name ?? $statusLabel }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                <dl class="mt-5 grid gap-4 md:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cliente</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->customer?->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Prazo prometido</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->deadline_at?->format('d/m/Y H:i') ?? 'A definir' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Orçamento</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ (float) $order->total_price > 0 ? 'R$ '.number_format((float) $order->total_price, 2, ',', '.') : 'Aguardando análise' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-5 rounded-lg bg-gray-50 p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sintomas informados</div>
                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $order->defect_symptoms }}</p>
                </div>

                @if ($order->budget_decided_at)
                    <p class="mt-4 text-sm text-gray-600">
                        Resposta do orçamento registrada em {{ $order->budget_decided_at->format('d/m/Y H:i') }}.
                    </p>
                @endif

                @if ($order->status === 'approved' && (float) $order->total_price > 0)
                    <form method="POST" action="{{ route('public.checkout.budget', [$tenant->slug, $order->id]) }}" class="mt-5 border-t border-gray-200 pt-5">
                        @csrf
                        <input type="hidden" name="lookup" value="{{ $lookup }}">
                        <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                            <select name="payment_method" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="pix">Pix</option>
                                <option value="card">Cartão</option>
                            </select>
                            <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Criar pagamento em aberto
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Nenhum pagamento real será processado neste momento.</p>
                    </form>
                @endif

                @if ($canDecideBudget)
                    <div class="mt-5 flex flex-col gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
                        <form method="POST" action="{{ route('public.tracking.budget', [$tenant->slug, $order->id]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="lookup" value="{{ $lookup }}">
                            <input type="hidden" name="decision" value="rejected">
                            <button class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 sm:w-auto">
                                Recusar orçamento
                            </button>
                        </form>
                        <form method="POST" action="{{ route('public.tracking.budget', [$tenant->slug, $order->id]) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="lookup" value="{{ $lookup }}">
                            <input type="hidden" name="decision" value="approved">
                            <button class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 sm:w-auto">
                                Aprovar orçamento
                            </button>
                        </form>
                    </div>
                @endif
            </article>
        @endforeach
    </div>
@endsection
