@extends('layouts.admin')

@section('title', 'Planos')

@section('content')
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Superadmin</p>
                <h1 class="text-3xl font-bold text-gray-950">Planos da plataforma</h1>
                <p class="mt-1 text-sm text-gray-600">Gerencie valores, trial e limitações técnicas de cada assinatura.</p>
            </div>

            <a href="{{ route('planos.create') }}" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Novo plano
            </a>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Plano</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Mensal</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Anual</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Limites</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Assistências</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($plans as $plan)
                            @php($features = $plan->features ?? [])
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-950">{{ $plan->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $plan->trial_days_allowed }} dias de trial</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">R$ {{ number_format((float) $plan->price_monthly, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    @if ($plan->price_yearly)
                                        R$ {{ number_format((float) $plan->price_yearly, 2, ',', '.') }}
                                    @else
                                        <span class="text-gray-400">Não definido</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div>OS/mês: {{ $features['max_service_orders_per_month'] ?? 'Ilimitado' }}</div>
                                    <div>Usuários: {{ $features['max_users'] ?? 'Ilimitado' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $plan->tenants_count }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('planos.edit', $plan) }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                            Editar
                                        </a>

                                        <form method="POST" action="{{ route('planos.destroy', $plan) }}" onsubmit="return confirm('Excluir este plano? Esta ação não pode ser desfeita.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50" @disabled($plan->tenants_count > 0)>
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Nenhum plano cadastrado ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $plans->links() }}
    </div>
@endsection
