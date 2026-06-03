<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $startDate = $request->date('start_date') ?? now()->startOfMonth();
        $endDate = $request->date('end_date') ?? now()->endOfMonth();

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $serviceOrders = ServiceOrder::with(['customer', 'kanbanColumn'])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('deadline_at')
            ->whereBetween('deadline_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->orderBy('deadline_at')
            ->get();

        $overdueOrders = ServiceOrder::with(['customer', 'kanbanColumn'])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', now()->startOfDay())
            ->whereNotIn('status', ['finished', 'rejected'])
            ->orderBy('deadline_at')
            ->get();

        $todayOrders = ServiceOrder::with(['customer', 'kanbanColumn'])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('deadline_at')
            ->whereBetween('deadline_at', [now()->startOfDay(), now()->endOfDay()])
            ->whereNotIn('status', ['finished', 'rejected'])
            ->orderBy('deadline_at')
            ->get();

        $hardwareReceipts = ServiceOrder::with('customer')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('hardware_received_at')
            ->whereBetween('hardware_received_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->orderByDesc('hardware_received_at')
            ->get();

        $calendarDays = $this->buildCalendarDays($startDate, $endDate, $serviceOrders);

        return view('tenant.schedule.index', compact(
            'calendarDays',
            'endDate',
            'hardwareReceipts',
            'overdueOrders',
            'serviceOrders',
            'startDate',
            'todayOrders'
        ));
    }

    public function updateServiceOrderSchedule(Request $request, $id)
    {
        $serviceOrder = ServiceOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'deadline_at' => 'nullable|date',
            'hardware_received_at' => 'nullable|date',
            'hardware_received_notes' => 'nullable|string|max:1000',
            'mark_hardware_received' => 'nullable|boolean',
        ]);

        if ($request->boolean('mark_hardware_received') && empty($validated['hardware_received_at'])) {
            $validated['hardware_received_at'] = now();
        }

        $updates = [];

        if ($request->has('deadline_at')) {
            $updates['deadline_at'] = $validated['deadline_at'] ?? null;
        }

        if ($request->has('hardware_received_at') || $request->boolean('mark_hardware_received')) {
            $updates['hardware_received_at'] = $validated['hardware_received_at'] ?? null;
        }

        if ($request->has('hardware_received_notes')) {
            $updates['hardware_received_notes'] = $validated['hardware_received_notes'] ?? null;
        }

        $serviceOrder->update($updates);

        return redirect()
            ->route('tenant.schedule.index')
            ->with('success', 'Agenda da OS atualizada.');
    }

    private function buildCalendarDays(Carbon $startDate, Carbon $endDate, $serviceOrders): array
    {
        $ordersByDate = $serviceOrders->groupBy(fn (ServiceOrder $order) => $order->deadline_at->toDateString());
        $days = [];

        for ($date = $startDate->copy()->startOfDay(); $date->lte($endDate); $date->addDay()) {
            $days[] = [
                'date' => $date->copy(),
                'orders' => $ordersByDate->get($date->toDateString(), collect()),
            ];
        }

        return $days;
    }
}
