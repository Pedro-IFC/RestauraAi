<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    /**
     * Display a listing of the items.
     * (Lista o estoque da assistência. Pode receber filtros de busca, tipo ou estoque baixo).
     */
    public function index(Request $request)
    {
        // Exemplo prático de filtro: se passar ?low_stock=1 na URL, filtra os itens em alerta
        $query = Item::forTenant(Auth::user()->tenant_id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search')->toString().'%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->has('low_stock')) {
            $query->whereColumn('stock_quantity', '<=', 'min_stock_alert');
        }

        $items = $query->latest()->paginate(15);

        return view('tenant.items.index', compact('items'));
    }

    /**
     * Show the form for creating a new item.
     */
    public function create()
    {
        return view('tenant.items.create');
    }

    /**
     * Store a newly created item in storage.
     * (Valida e salva o produto/insumo, vinculando ao tenant_id do usuário logado).
     */
    public function store(Request $request)
    {
        $tenant = Auth::user()->tenant;

        if (($tenant->planLimit('max_items') !== null) && Item::forTenant($tenant->id)->count() >= $tenant->planLimit('max_items')) {
            return back()
                ->withInput()
                ->withErrors(['plan' => 'Limite de itens de estoque atingido para o plano atual.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,supply',
            'is_for_sale' => 'boolean',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|numeric|min:0',
            'min_stock_alert' => 'required|numeric|min:0',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|max:4096',
        ]);

        // Garantimos que checkboxes booleanos ausentes no request sejam tratados como false
        $validated['is_for_sale'] = $validated['type'] === 'product' && $request->has('is_for_sale');

        if ($validated['is_for_sale']) {
            $this->ensurePublicProductAllowed($tenant);
        }

        // Atribui o tenant_id da assistência atual
        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['images'] = $this->storeImages($request, $tenant->id);

        Item::create($validated);

        return redirect()
            ->route('estoque.index')
            ->with('success', 'Item adicionado ao estoque com sucesso.');
    }

    /**
     * Display the specified item.
     * (Opcional: Pode exibir um histórico de em quais Ordens de Serviço este item foi usado).
     */
    public function show($id)
    {
        // Carrega o item e as OSs onde ele foi utilizado (através do relacionamento pivot)
        $item = Item::forTenant(Auth::user()->tenant_id)->with(['serviceOrders' => function ($query) {
            $query->orderByDesc('service_orders.created_at')->limit(10); // Mostra as últimas 10 OSs que usaram esta peça
        }])->findOrFail($id);

        return view('tenant.items.show', compact('item'));
    }

    /**
     * Show the form for editing the specified item.
     */
    public function edit($id)
    {
        $item = Item::forTenant(Auth::user()->tenant_id)->findOrFail($id);

        return view('tenant.items.edit', compact('item'));
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, $id)
    {
        $tenant = Auth::user()->tenant;
        $item = Item::forTenant($tenant->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:product,supply',
            'is_for_sale' => 'boolean',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|numeric|min:0',
            'min_stock_alert' => 'sometimes|required|numeric|min:0',
            'images' => 'nullable|array|max:3',
            'images.*' => 'image|max:4096',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
        ]);

        // Trata o checkbox do form HTML (se não for enviado, é false)
        if ($request->has('is_for_sale_submitted')) {
            $validated['is_for_sale'] = ($validated['type'] ?? $item->type) === 'product' && $request->has('is_for_sale');
        }

        if (($validated['is_for_sale'] ?? false) && ! $item->is_for_sale) {
            $this->ensurePublicProductAllowed($tenant);
        }

        $validated['images'] = $this->syncImages($request, $item, $tenant->id);

        $item->update($validated);

        return redirect()
            ->route('estoque.index')
            ->with('success', 'Item atualizado com sucesso.');
    }

    /**
     * Remove the specified item from storage.
     * (Executa o Soft Delete. O item some do catálogo e do painel, mas não quebra as OSs antigas).
     */
    public function destroy($id)
    {
        $item = Item::forTenant(Auth::user()->tenant_id)->findOrFail($id);

        foreach ($item->images ?? [] as $image) {
            Storage::disk('public')->delete($image);
        }

        $item->delete();

        return redirect()
            ->route('estoque.index')
            ->with('success', 'Item removido do estoque.');
    }

    private function ensurePublicProductAllowed($tenant): void
    {
        if (! $tenant->hasFeature('catalog')) {
            abort(403, 'Catálogo público não disponível no plano atual.');
        }

        $limit = $tenant->planLimit('max_public_products');

        if ($limit !== null && Item::forTenant($tenant->id)->where('is_for_sale', true)->count() >= $limit) {
            abort(403, 'Limite de produtos públicos atingido para o plano atual.');
        }
    }

    private function storeImages(Request $request, int $tenantId): array
    {
        return collect($request->file('images', []))
            ->map(fn ($image) => $image->store('items/'.$tenantId, 'public'))
            ->values()
            ->all();
    }

    private function syncImages(Request $request, Item $item, int $tenantId): array
    {
        $existingImages = collect($item->images ?? []);
        $imagesToRemove = $existingImages
            ->intersect($request->input('remove_images', []))
            ->values();

        foreach ($imagesToRemove as $path) {
            Storage::disk('public')->delete($path);
        }

        $remainingImages = $existingImages
            ->reject(fn (string $path) => $imagesToRemove->contains($path))
            ->values();

        if (($remainingImages->count() + count($request->file('images', []))) > 3) {
            throw ValidationException::withMessages([
                'images' => 'Cada produto pode ter no máximo 3 imagens.',
            ]);
        }

        $newImages = $this->storeImages($request, $tenantId);

        return $remainingImages
            ->merge($newImages)
            ->take(3)
            ->values()
            ->all();
    }
}
