<?php

namespace App\Services;

use App\Models\KanbanColumn;
use App\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;

class ServiceOrderWorkflow
{
    public function advanceAfterHardwareReceipt(ServiceOrder $serviceOrder, ?int $targetColumnId = null): void
    {
        DB::transaction(function () use ($serviceOrder, $targetColumnId) {
            $order = ServiceOrder::where('tenant_id', $serviceOrder->tenant_id)
                ->lockForUpdate()
                ->findOrFail($serviceOrder->id);

            if (in_array($order->status, ['finished', 'rejected'], true)) {
                return;
            }

            $targetColumn = $targetColumnId
                ? KanbanColumn::where('tenant_id', $order->tenant_id)->find($targetColumnId)
                : $this->nextColumnFor($order);

            if (! $targetColumn || $targetColumn->id === $order->kanban_column_id) {
                return;
            }

            $oldColumnId = $order->kanban_column_id;
            $nextPosition = (ServiceOrder::where('tenant_id', $order->tenant_id)
                ->where('kanban_column_id', $targetColumn->id)
                ->max('kanban_position') ?? 0) + 1;

            $order->update([
                'kanban_column_id' => $targetColumn->id,
                'kanban_position' => $nextPosition,
                'status' => $this->statusForColumn($targetColumn->name, $order->status),
            ]);

            $this->normalizeColumnPositions($order->tenant_id, $oldColumnId);
            $this->normalizeColumnPositions($order->tenant_id, $targetColumn->id);
        });
    }

    private function nextColumnFor(ServiceOrder $order): ?KanbanColumn
    {
        $currentColumn = $order->kanbanColumn()
            ->where('tenant_id', $order->tenant_id)
            ->first();

        if (! $currentColumn) {
            return KanbanColumn::where('tenant_id', $order->tenant_id)
                ->orderBy('order_index')
                ->first();
        }

        return KanbanColumn::where('tenant_id', $order->tenant_id)
            ->where('order_index', '>', $currentColumn->order_index)
            ->orderBy('order_index')
            ->first();
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
