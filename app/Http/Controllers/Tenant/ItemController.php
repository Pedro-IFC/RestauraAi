<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of the items.
     * (Lista o estoque da assistência. Pode receber filtros de busca, tipo ou estoque baixo).
     */
    public function index(Request $request)
    {
        // Exemplo prático de filtro: se passar ?low_stock=1 na URL, filtra os itens em alerta
        $query = Item::query();

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
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'type'            => 'required|in:product,supply',
            'is_for_sale'     => 'boolean',
            'cost_price'      => 'required|numeric|min:0',
            'sale_price'      => 'required|numeric|min:0',
            'stock_quantity'  => 'required|integer|min:0',
            'min_stock_alert' => 'required|integer|min:0',
        ]);

        // Garantimos que checkboxes booleanos ausentes no request sejam tratados como false
        $validated['is_for_sale'] = $request->has('is_for_sale');
        
        // Atribui o tenant_id da assistência atual
        $validated['tenant_id'] = Auth::user()->tenant_id;

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
        $item = Item::with(['serviceOrders' => function ($query) {
            $query->latest()->limit(10); // Mostra as últimas 10 OSs que usaram esta peça
        }])->findOrFail($id);

        return view('tenant.items.show', compact('item'));
    }

    /**
     * Show the form for editing the specified item.
     */
    public function edit($id)
    {
        $item = Item::findOrFail($id);

        return view('tenant.items.edit', compact('item'));
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $validated = $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'type'            => 'sometimes|required|in:product,supply',
            'is_for_sale'     => 'boolean',
            'cost_price'      => 'sometimes|required|numeric|min:0',
            'sale_price'      => 'sometimes|required|numeric|min:0',
            'stock_quantity'  => 'sometimes|required|integer|min:0',
            'min_stock_alert' => 'sometimes|required|integer|min:0',
        ]);

        // Trata o checkbox do form HTML (se não for enviado, é false)
        if ($request->has('is_for_sale_submitted')) {
            $validated['is_for_sale'] = $request->has('is_for_sale');
        }

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
        $item = Item::findOrFail($id);
        
        $item->delete();

        return redirect()
            ->route('estoque.index')
            ->with('success', 'Item removido do estoque.');
    }
}