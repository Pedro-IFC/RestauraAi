@php
    $features = array_replace(\App\Models\Plan::defaultFeatures(), old('features', $plan->features ?? []));
    $planPresets = [
        'bronze' => \App\Models\Plan::presetFeatures('bronze'),
        'prata' => \App\Models\Plan::presetFeatures('prata'),
        'ouro' => \App\Models\Plan::presetFeatures('ouro'),
    ];
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700">Nome do plano</label>
        <input id="name" name="name" type="text" value="{{ old('name', $plan->name) }}" required placeholder="Plano Bronze" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="price_monthly" class="block text-sm font-medium text-gray-700">Valor mensal</label>
        <input id="price_monthly" name="price_monthly" type="number" step="0.01" min="0" value="{{ old('price_monthly', $plan->price_monthly) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="price_yearly" class="block text-sm font-medium text-gray-700">Valor anual</label>
        <input id="price_yearly" name="price_yearly" type="number" step="0.01" min="0" value="{{ old('price_yearly', $plan->price_yearly) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="trial_days_allowed" class="block text-sm font-medium text-gray-700">Dias de trial permitidos</label>
        <input id="trial_days_allowed" name="trial_days_allowed" type="number" min="0" max="365" value="{{ old('trial_days_allowed', $plan->trial_days_allowed) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-950">Limitações técnicas</h2>
    <p class="mt-1 text-sm text-gray-600">Deixe um limite vazio para considerar ilimitado.</p>

    <div class="mt-4 grid gap-5 md:grid-cols-2">
        <div>
            <label for="max_service_orders_per_month" class="block text-sm font-medium text-gray-700">Ordens de serviço por mês</label>
            <input id="max_service_orders_per_month" name="features[max_service_orders_per_month]" type="number" min="0" value="{{ $features['max_service_orders_per_month'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="max_users" class="block text-sm font-medium text-gray-700">Usuários internos</label>
            <input id="max_users" name="features[max_users]" type="number" min="0" value="{{ $features['max_users'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="max_items" class="block text-sm font-medium text-gray-700">Itens de estoque</label>
            <input id="max_items" name="features[max_items]" type="number" min="0" value="{{ $features['max_items'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="max_public_products" class="block text-sm font-medium text-gray-700">Produtos no catálogo público</label>
            <input id="max_public_products" name="features[max_public_products]" type="number" min="0" value="{{ $features['max_public_products'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
    </div>
</div>

<div class="mt-8">
    <h2 class="text-lg font-semibold text-gray-950">Recursos liberados</h2>
    <div class="mt-3 grid gap-3 md:grid-cols-3">
        @foreach ([
            'bronze' => 'Bronze',
            'prata' => 'Prata',
            'ouro' => 'Ouro',
        ] as $preset => $label)
            <button type="button"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                data-plan-preset="{{ $preset }}">
                Aplicar {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach (\App\Models\Plan::featureLabels() as $key => $label)
            <label class="flex items-center gap-3 rounded-md border border-gray-200 px-4 py-3 text-sm font-medium text-gray-700">
                <input type="hidden" name="features[{{ $key }}]" value="0">
                <input type="checkbox" name="features[{{ $key }}]" value="1" @checked((bool) ($features[$key] ?? false)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                {{ $label }}
            </label>
        @endforeach
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
    <a href="{{ route('planos.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
        Cancelar
    </a>
    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
        Salvar plano
    </button>
</div>

@push('scripts')
    <script>
        (() => {
            const presets = @json($planPresets);

            document.querySelectorAll('[data-plan-preset]').forEach((button) => {
                button.addEventListener('click', () => {
                    const features = presets[button.dataset.planPreset] || {};

                    Object.entries(features).forEach(([key, value]) => {
                        const checkbox = document.querySelector(`input[type="checkbox"][name="features[${key}]"]`);
                        const input = document.querySelector(`input:not([type="hidden"])[name="features[${key}]"]`);

                        if (checkbox) {
                            checkbox.checked = Boolean(value);
                        }

                        if (input && !checkbox) {
                            input.value = value === null ? '' : value;
                        }
                    });
                });
            });
        })();
    </script>
@endpush
