<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\Customer;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ServiceOrderController extends Controller
{
    /**
     * Display a listing of the service orders.
     * (Lista as OSs da assistência atual. O escopo do Tenant pode ser injetado via Global Scope)
     */
    public function index()
    {
        // Exemplo de listagem com paginação e carregamento dos relacionamentos necessários
        $serviceOrders = ServiceOrder::with(['customer', 'kanbanColumn'])
            ->latest()
            ->paginate(15);

        return view('tenant.service_orders.index', compact('serviceOrders'));
    }

    /**
     * Show the form for creating a new service order.
     * (Carrega os dados necessários para abrir uma OS internamente, como a lista de clientes)
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        
        return view('tenant.service_orders.create', compact('customers'));
    }

    /**
     * Store a newly created service order in storage.
     * (Valida e salva a nova OS informada pelo técnico ou vinda do portal público RF-03)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id'      => 'required|exists:customers,id',
            'device_model'     => 'required|string|max:255',
            'defect_symptoms'  => 'required|string',
            'kanban_column_id' => 'required|exists:kanban_columns,id',
            'deadline_at'      => 'nullable|date|after:today',
        ]);

        // O tenant_id pode ser definido automaticamente no observer ou aqui via auth()
        $serviceOrder = ServiceOrder::create(array_merge($validated, [
            'status' => 'pending',
            'total_cost' => 0.00,
            'total_price' => 0.00,
        ]));

        return redirect()
            ->route('ordens-servico.show', $serviceOrder->id)
            ->with('success', 'Ordem de Serviço criada com sucesso.');
    }

    /**
     * Display the specified service order.
     * (Exibe os detalhes de uma OS específica, incluindo histórico de tempo e peças usadas)
     */
    public function show($id)
    {
        $serviceOrder = ServiceOrder::with(['customer', 'kanbanColumn', 'items', 'timeEntries.user'])
            ->findOrFail($id);

        return view('tenant.service_orders.show', compact('serviceOrder'));
    }

    /**
     * Show the form for editing the specified service order.
     */
    public function edit($id)
    {
        $serviceOrder = ServiceOrder::findOrFail($id);
        $customers = Customer::orderBy('name')->get();

        return view('tenant.service_orders.edit', compact('serviceOrder', 'customers'));
    }

    /**
     * Update the specified service order in storage.
     * (Atualiza dados do aparelho, prazos ou alteração manual de status)
     */
    public function update(Request $request, $id)
    {
        $serviceOrder = ServiceOrder::findOrFail($id);

        $validated = $request->validate([
            'device_model'    => 'sometimes|required|string|max:255',
            'defect_symptoms' => 'sometimes|required|string',
            'status'          => 'sometimes|required|in:pending,budgeting,approved,rejected,finished',
            'deadline_at'     => 'nullable|date',
        ]);

        $serviceOrder->update($validated);

        return redirect()
            ->route('ordens-servico.show', $serviceOrder->id)
            ->with('success', 'Ordem de Serviço atualizada com sucesso.');
    }

    /**
     * Remove the specified service order from storage.
     * (Executa o Soft Delete preservando o histórico para o Dashboard financeiro RF-10)
     */
    public function destroy($id)
    {
        $serviceOrder = ServiceOrder::findOrFail($id);
        $serviceOrder->delete();

        return redirect()
            ->route('ordens-servico.index')
            ->with('success', 'Ordem de Serviço removida com sucesso.');
    }

    /**
     * Attach items/supplies to the service order.
     * (Rota customizada: Vincula peças/insumos consumidos na bancada, dando baixa no estoque)
     */
    public function attachItems(Request $request, $id)
    {
        $serviceOrder = ServiceOrder::findOrFail($id);

        $validated = $request->validate([
            'item_id'  => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $item = Item::findOrFail($validated['item_id']);

        // 1. Verifica se há estoque suficiente para a operação
        if ($item->stock_quantity < $validated['quantity']) {
            return redirect()->back()->withErrors(['quantity' => 'Estoque insuficiente para este insumo.']);
        }

        // 2. Registra na tabela intermediária salvando o SNAPSHOT dos valores atuais
        $serviceOrder->items()->attach($item->id, [
            'quantity'   => $validated['quantity'],
            'unit_cost'  => $item->cost_price,
            'unit_price' => $item->sale_price,
        ]);

        // 3. Deduz a quantidade do estoque do item
        $item->decrement('stock_quantity', $validated['quantity']);

        // 4. Recalcula os totais de custo e preço da Ordem de Serviço
        $totalCost  = $serviceOrder->items()->sum(DB::raw('service_order_items.quantity * service_order_items.unit_cost'));
        $totalPrice = $serviceOrder->items()->sum(DB::raw('service_order_items.quantity * service_order_items.unit_price'));

        $serviceOrder->update([
            'total_cost'  => $totalCost,
            'total_price' => $totalPrice,
        ]);

        return redirect()
            ->route('ordens-servico.show', $serviceOrder->id)
            ->with('success', 'Insumo adicionado e estoque atualizado.');
    }
}