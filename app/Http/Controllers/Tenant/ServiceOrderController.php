<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Item;
use App\Models\KanbanColumn;
use App\Models\ServiceOrder;
use App\Services\ServiceOrderWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            ->where('tenant_id', Auth::user()->tenant_id)
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
        $customers = Customer::where('tenant_id', Auth::user()->tenant_id)->orderBy('name')->get();
        $kanbanColumns = KanbanColumn::where('tenant_id', Auth::user()->tenant_id)->orderBy('order_index')->get();

        return view('tenant.service_orders.create', compact('customers', 'kanbanColumns'));
    }

    /**
     * Store a newly created service order in storage.
     * (Valida e salva a nova OS informada pelo técnico ou vinda do portal público RF-03)
     */
    public function store(Request $request)
    {
        if (Auth::user()->tenant->hasReachedMonthlyServiceOrderLimit()) {
            return back()
                ->withInput()
                ->withErrors(['plan' => 'Limite mensal de chamados atingido para o plano atual.']);
        }

        $validated = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
            'device_model' => 'required|string|max:255',
            'defect_symptoms' => 'required|string',
            'kanban_column_id' => [
                'required',
                Rule::exists('kanban_columns', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
            'planned_start_at' => 'nullable|date',
            'deadline_at' => 'nullable|date',
            'hardware_received_at' => 'nullable|date',
            'hardware_received_notes' => 'nullable|string|max:1000',
            'schedule_notes' => 'nullable|string|max:1000',
            'next_kanban_column_id' => [
                'nullable',
                Rule::exists('kanban_columns', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
        ]);

        $nextKanbanColumnId = $validated['next_kanban_column_id'] ?? null;
        unset($validated['next_kanban_column_id']);

        // O tenant_id pode ser definido automaticamente no observer ou aqui via auth()
        $serviceOrder = ServiceOrder::create(array_merge($validated, [
            'tenant_id' => Auth::user()->tenant_id,
            'status' => 'pending',
            'kanban_position' => (ServiceOrder::where('tenant_id', Auth::user()->tenant_id)
                ->where('kanban_column_id', $validated['kanban_column_id'])
                ->max('kanban_position') ?? 0) + 1,
            'total_cost' => 0.00,
            'total_price' => 0.00,
        ]));

        if ($serviceOrder->hardware_received_at) {
            app(ServiceOrderWorkflow::class)->advanceAfterHardwareReceipt($serviceOrder, $nextKanbanColumnId);
        }

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
            ->where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($id);

        $availableItems = Auth::user()->tenant->hasFeature('inventory')
            ? Item::forTenant(Auth::user()->tenant_id)
                ->where('stock_quantity', '>', 0)
                ->orderBy('type')
                ->orderBy('name')
                ->get()
            : collect();
        $kanbanColumns = KanbanColumn::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('order_index')
            ->get();

        return view('tenant.service_orders.show', compact('serviceOrder', 'availableItems', 'kanbanColumns'));
    }

    /**
     * Show the form for editing the specified service order.
     */
    public function edit($id)
    {
        $serviceOrder = ServiceOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $customers = Customer::where('tenant_id', Auth::user()->tenant_id)->orderBy('name')->get();
        $kanbanColumns = KanbanColumn::where('tenant_id', Auth::user()->tenant_id)->orderBy('order_index')->get();

        return view('tenant.service_orders.edit', compact('serviceOrder', 'customers', 'kanbanColumns'));
    }

    /**
     * Update the specified service order in storage.
     * (Atualiza dados do aparelho, prazos ou alteração manual de status)
     */
    public function update(Request $request, $id)
    {
        $serviceOrder = ServiceOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'device_model' => 'sometimes|required|string|max:255',
            'defect_symptoms' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:pending,budgeting,approved,rejected,finished',
            'total_price' => 'sometimes|numeric|min:0',
            'planned_start_at' => 'nullable|date',
            'deadline_at' => 'nullable|date',
            'hardware_received_at' => 'nullable|date',
            'hardware_received_notes' => 'nullable|string|max:1000',
            'schedule_notes' => 'nullable|string|max:1000',
            'next_kanban_column_id' => [
                'nullable',
                Rule::exists('kanban_columns', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
        ]);

        $nextKanbanColumnId = $validated['next_kanban_column_id'] ?? null;
        unset($validated['next_kanban_column_id']);

        $wasHardwareReceived = (bool) $serviceOrder->hardware_received_at;
        $serviceOrder->update($validated);

        if (! $wasHardwareReceived && $serviceOrder->fresh()->hardware_received_at) {
            app(ServiceOrderWorkflow::class)->advanceAfterHardwareReceipt(
                $serviceOrder,
                $nextKanbanColumnId
            );
        }

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
        $serviceOrder = ServiceOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
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
        $serviceOrder = ServiceOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'item_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::transaction(function () use ($serviceOrder, $validated) {
                $item = Item::forTenant(Auth::user()->tenant_id)
                    ->lockForUpdate()
                    ->findOrFail($validated['item_id']);

                // 1. Verifica se há estoque suficiente para a operação
                if ((float) $item->stock_quantity < (float) $validated['quantity']) {
                    throw new \RuntimeException('Estoque insuficiente para este insumo.');
                }

                // 2. Registra na tabela intermediária salvando o SNAPSHOT dos valores atuais
                $serviceOrder->items()->attach($item->id, [
                    'quantity' => $validated['quantity'],
                    'unit_cost' => $item->cost_price,
                    'unit_price' => $item->sale_price,
                ]);

                // 3. Deduz a quantidade do estoque do item
                $item->decrement('stock_quantity', $validated['quantity']);

                // 4. Recalcula os totais de custo e preço da Ordem de Serviço
                $totalCost = $serviceOrder->items()->sum(DB::raw('service_order_items.quantity * service_order_items.unit_cost'));
                $totalPrice = $serviceOrder->items()->sum(DB::raw('service_order_items.quantity * service_order_items.unit_price'));

                $serviceOrder->update([
                    'total_cost' => $totalCost,
                    'total_price' => $totalPrice,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return redirect()->back()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()
            ->route('ordens-servico.show', $serviceOrder->id)
            ->with('success', 'Insumo adicionado e estoque atualizado.');
    }
}
