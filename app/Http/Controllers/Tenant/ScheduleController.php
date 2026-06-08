<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\KanbanColumn;
use App\Models\ScheduleEvent;
use App\Models\ServiceOrder;
use App\Services\ServiceOrderWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            ->where(function ($query) use ($startDate, $endDate) {
                $query
                    ->whereBetween('planned_start_at', [
                        $startDate->copy()->startOfDay(),
                        $endDate->copy()->endOfDay(),
                    ])
                    ->orWhereBetween('deadline_at', [
                        $startDate->copy()->startOfDay(),
                        $endDate->copy()->endOfDay(),
                    ]);
            })
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

        $events = ScheduleEvent::with(['serviceOrder.customer', 'creator'])
            ->where('tenant_id', $tenantId)
            ->whereBetween('starts_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->orderBy('starts_at')
            ->get();

        $dueReminders = ScheduleEvent::with('serviceOrder')
            ->where('tenant_id', $tenantId)
            ->whereNull('completed_at')
            ->whereNotNull('remind_at')
            ->where('remind_at', '<=', now())
            ->orderBy('remind_at')
            ->get();

        $availableServiceOrders = ServiceOrder::with(['customer', 'kanbanColumn'])
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['finished', 'rejected'])
            ->latest()
            ->limit(50)
            ->get();

        $kanbanColumns = KanbanColumn::where('tenant_id', $tenantId)
            ->orderBy('order_index')
            ->get();

        $eventTypeLabels = ScheduleEvent::typeLabels();
        $calendarDays = $this->buildCalendarDays($startDate, $endDate, $serviceOrders, $events);

        return view('tenant.schedule.index', compact(
            'availableServiceOrders',
            'calendarDays',
            'dueReminders',
            'endDate',
            'events',
            'eventTypeLabels',
            'hardwareReceipts',
            'kanbanColumns',
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
            'planned_start_at' => 'nullable|date',
            'hardware_received_at' => 'nullable|date',
            'hardware_received_notes' => 'nullable|string|max:1000',
            'schedule_notes' => 'nullable|string|max:1000',
            'mark_hardware_received' => 'nullable|boolean',
            'next_kanban_column_id' => [
                'nullable',
                Rule::exists('kanban_columns', 'id')->where('tenant_id', Auth::user()->tenant_id),
            ],
        ]);

        if ($request->boolean('mark_hardware_received') && empty($validated['hardware_received_at'])) {
            $validated['hardware_received_at'] = now();
        }

        $wasHardwareReceived = (bool) $serviceOrder->hardware_received_at;
        $updates = [];

        if ($request->has('planned_start_at')) {
            $updates['planned_start_at'] = $validated['planned_start_at'] ?? null;
        }

        if ($request->has('deadline_at')) {
            $updates['deadline_at'] = $validated['deadline_at'] ?? null;
        }

        if ($request->has('hardware_received_at') || $request->boolean('mark_hardware_received')) {
            $updates['hardware_received_at'] = $validated['hardware_received_at'] ?? null;
        }

        if ($request->has('hardware_received_notes')) {
            $updates['hardware_received_notes'] = $validated['hardware_received_notes'] ?? null;
        }

        if ($request->has('schedule_notes')) {
            $updates['schedule_notes'] = $validated['schedule_notes'] ?? null;
        }

        $serviceOrder->update($updates);

        $didReceiveHardware = ! $wasHardwareReceived && (bool) $serviceOrder->fresh()->hardware_received_at;

        if ($didReceiveHardware) {
            app(ServiceOrderWorkflow::class)->advanceAfterHardwareReceipt(
                $serviceOrder,
                $validated['next_kanban_column_id'] ?? null
            );
        }

        return redirect()
            ->route('tenant.schedule.index')
            ->with('success', $didReceiveHardware ? 'Hardware recebido e OS encaminhada.' : 'Agenda da OS atualizada.');
    }

    public function storeEvent(Request $request)
    {
        $validated = $this->validateEvent($request);

        ScheduleEvent::create(array_merge($validated, [
            'tenant_id' => Auth::user()->tenant_id,
            'created_by' => Auth::id(),
        ]));

        return redirect()
            ->route('tenant.schedule.index')
            ->with('success', 'Evento cadastrado na agenda.');
    }

    public function updateEvent(Request $request, ScheduleEvent $event)
    {
        $this->authorizeTenantEvent($event);

        if ($request->boolean('complete')) {
            $event->update(['completed_at' => $event->completed_at ?? now()]);

            return redirect()
                ->route('tenant.schedule.index')
                ->with('success', 'Lembrete marcado como concluido.');
        }

        $event->update($this->validateEvent($request));

        return redirect()
            ->route('tenant.schedule.index')
            ->with('success', 'Evento atualizado.');
    }

    public function destroyEvent(ScheduleEvent $event)
    {
        $this->authorizeTenantEvent($event);
        $event->delete();

        return redirect()
            ->route('tenant.schedule.index')
            ->with('success', 'Evento removido da agenda.');
    }

    private function buildCalendarDays(Carbon $startDate, Carbon $endDate, $serviceOrders, $events): array
    {
        $ordersByDate = collect();

        foreach ($serviceOrders as $order) {
            foreach ([$order->planned_start_at, $order->deadline_at] as $date) {
                if (! $date) {
                    continue;
                }

                $ordersByDate->put(
                    $date->toDateString(),
                    $ordersByDate->get($date->toDateString(), collect())->push($order)
                );
            }
        }

        $eventsByDate = $events->groupBy(fn (ScheduleEvent $event) => $event->starts_at->toDateString());
        $days = [];

        for ($date = $startDate->copy()->startOfDay(); $date->lte($endDate); $date->addDay()) {
            $days[] = [
                'date' => $date->copy(),
                'orders' => $ordersByDate->get($date->toDateString(), collect())->unique('id')->sortBy(fn (ServiceOrder $order) => $order->deadline_at ?? $order->planned_start_at),
                'events' => $eventsByDate->get($date->toDateString(), collect()),
            ];
        }

        return $days;
    }

    private function validateEvent(Request $request): array
    {
        $tenantId = Auth::user()->tenant_id;

        return $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(ScheduleEvent::typeLabels()))],
            'service_order_id' => [
                'nullable',
                Rule::exists('service_orders', 'id')->where('tenant_id', $tenantId),
            ],
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'remind_at' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);
    }

    private function authorizeTenantEvent(ScheduleEvent $event): void
    {
        abort_unless($event->tenant_id === Auth::user()->tenant_id, 404);
    }
}
