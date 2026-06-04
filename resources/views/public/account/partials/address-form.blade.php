@php
    $address ??= null;
    $fieldId = 'address_'.($address?->id ?? 'new');
@endphp

<div>
    <label for="{{ $fieldId }}_label" class="block text-sm font-medium text-gray-700">Identificação</label>
    <input id="{{ $fieldId }}_label" name="label" value="{{ old('label', $address?->label ?? 'Principal') }}" required
        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div class="grid gap-3 md:grid-cols-2">
    <div>
        <label for="{{ $fieldId }}_recipient_name" class="block text-sm font-medium text-gray-700">Recebedor</label>
        <input id="{{ $fieldId }}_recipient_name" name="recipient_name" value="{{ old('recipient_name', $address?->recipient_name) }}"
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
    <div>
        <label for="{{ $fieldId }}_phone" class="block text-sm font-medium text-gray-700">Telefone</label>
        <input id="{{ $fieldId }}_phone" name="phone" value="{{ old('phone', $address?->phone) }}"
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<div class="grid gap-3 md:grid-cols-[1fr_110px]">
    <div>
        <label for="{{ $fieldId }}_street" class="block text-sm font-medium text-gray-700">Rua</label>
        <input id="{{ $fieldId }}_street" name="street" value="{{ old('street', $address?->street) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
    <div>
        <label for="{{ $fieldId }}_number" class="block text-sm font-medium text-gray-700">Número</label>
        <input id="{{ $fieldId }}_number" name="number" value="{{ old('number', $address?->number) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<div>
    <label for="{{ $fieldId }}_complement" class="block text-sm font-medium text-gray-700">Complemento</label>
    <input id="{{ $fieldId }}_complement" name="complement" value="{{ old('complement', $address?->complement) }}"
        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
</div>

<div class="grid gap-3 md:grid-cols-2">
    <div>
        <label for="{{ $fieldId }}_neighborhood" class="block text-sm font-medium text-gray-700">Bairro</label>
        <input id="{{ $fieldId }}_neighborhood" name="neighborhood" value="{{ old('neighborhood', $address?->neighborhood) }}"
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
    <div>
        <label for="{{ $fieldId }}_postal_code" class="block text-sm font-medium text-gray-700">CEP</label>
        <input id="{{ $fieldId }}_postal_code" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}"
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<div class="grid gap-3 md:grid-cols-[1fr_90px]">
    <div>
        <label for="{{ $fieldId }}_city" class="block text-sm font-medium text-gray-700">Cidade</label>
        <input id="{{ $fieldId }}_city" name="city" value="{{ old('city', $address?->city) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
    <div>
        <label for="{{ $fieldId }}_state" class="block text-sm font-medium text-gray-700">UF</label>
        <input id="{{ $fieldId }}_state" name="state" maxlength="2" value="{{ old('state', $address?->state) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 uppercase shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>
</div>

<label class="flex items-center gap-2 text-sm text-gray-600">
    <input type="hidden" name="is_default" value="0">
    <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $address?->is_default ?? false))
        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
    Usar como endereço padrão
</label>
