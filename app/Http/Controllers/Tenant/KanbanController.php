<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\KanbanColumn;
use App\Models\ServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KanbanController extends Controller
{
    private const DEFAULT_COLUMNS = [
        'Triagem',
        'Orçamento',
        'Aguardando Peça',
        'Na Bancada',
        'Testes',
        'Pronto',
    ];

    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $this->ensureDefaultColumns($tenantId);

        $columns = KanbanColumn::with(['serviceOrders' => function ($query) use ($tenantId) {
            $query
                ->where('tenant_id', $tenantId)
                ->with(['customer', 'timeEntries.user'])
                ->orderBy('kanban_position')
                ->orderBy('created_at');
        }])
            ->where('tenant_id', $tenantId)
            ->orderBy('order_index')
            ->get();

        return view('tenant.kanban.index', compact('columns'));
    }

    public function updateCardPosition(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'service_order_id' => [
                'required',
                Rule::exists('service_orders', 'id')->where('tenant_id', $tenantId),
            ],
            'kanban_column_id' => [
                'required',
                Rule::exists('kanban_columns', 'id')->where('tenant_id', $tenantId),
            ],
            'position' => 'nullable|integer|min:1',
        ]);

        DB::transaction(function () use ($tenantId, $validated) {
            $serviceOrder = ServiceOrder::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->findOrFail($validated['service_order_id']);

            $targetColumn = KanbanColumn::where('tenant_id', $tenantId)
                ->findOrFail($validated['kanban_column_id']);

            $oldColumnId = $serviceOrder->kanban_column_id;
            $nextPosition = $validated['position']
                ?? (ServiceOrder::where('tenant_id', $tenantId)
                    ->where('kanban_column_id', $targetColumn->id)
                    ->max('kanban_position') + 1);

            ServiceOrder::where('tenant_id', $tenantId)
                ->where('kanban_column_id', $targetColumn->id)
                ->where('id', '!=', $serviceOrder->id)
                ->where('kanban_position', '>=', $nextPosition)
                ->increment('kanban_position');

            $serviceOrder->update([
                'kanban_column_id' => $targetColumn->id,
                'kanban_position' => $nextPosition,
                'status' => $this->statusForColumn($targetColumn->name, $serviceOrder->status),
            ]);

            $this->normalizeColumnPositions($tenantId, $oldColumnId);
            $this->normalizeColumnPositions($tenantId, $targetColumn->id);
        });

        return redirect()
            ->route('tenant.kanban.index')
            ->with('success', 'Cartão movido no Kanban.');
    }

    public function storeColumn(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:80',
        ]);

        KanbanColumn::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'order_index' => (KanbanColumn::where('tenant_id', $tenantId)->max('order_index') ?? 0) + 1,
        ]);

        return redirect()
            ->route('tenant.kanban.index')
            ->with('success', 'Coluna criada.');
    }

    public function updateColumn(Request $request, KanbanColumn $column)
    {
        $this->authorizeTenantColumn($column);

        $validated = $request->validate([
            'name' => 'required|string|max:80',
            'order_index' => 'required|integer|min:1',
        ]);

        $column->update($validated);

        return redirect()
            ->route('tenant.kanban.index')
            ->with('success', 'Coluna atualizada.');
    }

    public function destroyColumn(KanbanColumn $column)
    {
        $this->authorizeTenantColumn($column);

        if ($column->serviceOrders()->exists()) {
            return redirect()
                ->back()
                ->withErrors(['column' => 'Não é possível remover uma coluna com chamados vinculados.']);
        }

        $column->delete();

        return redirect()
            ->route('tenant.kanban.index')
            ->with('success', 'Coluna removida.');
    }

    private function ensureDefaultColumns(int $tenantId): void
    {
        if (KanbanColumn::where('tenant_id', $tenantId)->exists()) {
            return;
        }

        foreach (self::DEFAULT_COLUMNS as $index => $name) {
            KanbanColumn::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'order_index' => $index + 1,
            ]);
        }
    }

    private function normalizeColumnPositions(int $tenantId, int $columnId): void
    {
        ServiceOrder::where('tenant_id', $tenantId)
            ->where('kanban_column_id', $columnId)
            ->orderBy('kanban_position')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function (ServiceOrder $order, int $index) {
                $order->update(['kanban_position' => $index + 1]);
            });
    }

    private function authorizeTenantColumn(KanbanColumn $column): void
    {
        abort_unless($column->tenant_id === Auth::user()->tenant_id, 404);
    }

    private function statusForColumn(string $columnName, string $currentStatus): string
    {
        $normalized = str($columnName)->ascii()->lower()->toString();

        return match (true) {
            str_contains($normalized, 'orcamento') => 'budgeting',
            str_contains($normalized, 'pronto'),
            str_contains($normalized, 'finalizado') => 'finished',
            str_contains($normalized, 'rejeitado') => 'rejected',
            str_contains($normalized, 'aprovado') => 'approved',
            default => $currentStatus,
        };
    }
}
