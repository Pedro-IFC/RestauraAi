@csrf

@if ($item->exists)
    @method('PUT')
@endif

<input type="hidden" name="is_for_sale_submitted" value="1">

<div class="grid gap-6 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="name" class="block text-sm font-medium text-gray-700">Nome do item</label>
        <input id="name" name="name" type="text" value="{{ old('name', $item->name) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="type" class="block text-sm font-medium text-gray-700">Destino do item</label>
        <select id="type" name="type" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="product" @selected(old('type', $item->type) === 'product')>Produto de venda</option>
            <option value="supply" @selected(old('type', $item->type) === 'supply')>Insumo técnico</option>
        </select>
        <p class="mt-1 text-xs text-gray-500">Insumos técnicos ficam restritos à bancada.</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
        <label class="flex items-start gap-3">
            <input name="is_for_sale" type="checkbox" value="1"
                @checked(old('is_for_sale', $item->is_for_sale))
                class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span>
                <span class="block text-sm font-semibold text-gray-800">Exibir no catálogo público</span>
                <span class="block text-xs text-gray-500">Quando marcado, produtos com estoque aparecem automaticamente na vitrine online.</span>
            </span>
        </label>
    </div>

    <div>
        <label for="cost_price" class="block text-sm font-medium text-gray-700">Custo unitário</label>
        <input id="cost_price" name="cost_price" type="number" step="0.01" min="0"
            value="{{ old('cost_price', $item->cost_price ?? 0) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="sale_price" class="block text-sm font-medium text-gray-700">Preço cobrado</label>
        <input id="sale_price" name="sale_price" type="number" step="0.01" min="0"
            value="{{ old('sale_price', $item->sale_price ?? 0) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="stock_quantity" class="block text-sm font-medium text-gray-700">Quantidade em estoque</label>
        <input id="stock_quantity" name="stock_quantity" type="number" step="0.01" min="0"
            value="{{ old('stock_quantity', $item->stock_quantity ?? 0) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div>
        <label for="min_stock_alert" class="block text-sm font-medium text-gray-700">Alerta de estoque mínimo</label>
        <input id="min_stock_alert" name="min_stock_alert" type="number" step="0.01" min="0"
            value="{{ old('min_stock_alert', $item->min_stock_alert ?? 5) }}" required
            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
    </div>

    <div class="md:col-span-2">
        <label for="images" class="block text-sm font-medium text-gray-700">Imagens do produto</label>
        <input id="images" name="images[]" type="file" accept="image/*" multiple
            class="mt-1 block w-full rounded-lg border border-gray-300 text-sm text-gray-700 file:mr-4 file:border-0 file:bg-yellow-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-orange-700 hover:file:bg-yellow-100">
        <p class="mt-1 text-xs text-gray-500">Cadastre até 3 imagens. Elas aparecem no catálogo público quando o item estiver marcado para venda.</p>

        @if (filled($item->images))
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                @foreach ($item->images as $image)
                    <label class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                        <img src="{{ asset('storage/'.$image) }}" alt="Imagem atual de {{ $item->name }}" class="h-32 w-full object-cover">
                        <span class="flex items-center gap-2 p-3 text-sm text-gray-700">
                            <input type="checkbox" name="remove_images[]" value="{{ $image }}" class="rounded border-gray-300 text-orange-600">
                            Remover
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="mt-8 flex items-center justify-end gap-3">
    <a href="{{ route('estoque.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
        Cancelar
    </a>
    <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
        Salvar item
    </button>
</div>
