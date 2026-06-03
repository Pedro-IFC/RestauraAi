<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $tenant = Auth::user()->tenant;

        if (! $tenant->hasFeature('dashboard')) {
            if ($tenant->hasFeature('kanban')) {
                return redirect()->route('tenant.kanban.index');
            }

            return redirect()->route('tenant.plan.blocked');
        }

        $tenantId = $tenant->id;
        $month = $request->date('month') ?? now();
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $finishedOrdersThisMonth = ServiceOrder::with('timeEntries')
            ->where('tenant_id', $tenantId)
            ->where('status', 'finished')
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->get();

        $finishedOrdersToday = $finishedOrdersThisMonth
            ->filter(fn (ServiceOrder $order) => $order->updated_at->isToday());

        $dailyNetRevenue = $finishedOrdersToday->sum(fn (ServiceOrder $order) => (float) $order->total_price - (float) $order->total_cost);
        $monthlyNetRevenue = $finishedOrdersThisMonth->sum(fn (ServiceOrder $order) => (float) $order->total_price - (float) $order->total_cost);
        $monthlyGrossRevenue = $finishedOrdersThisMonth->sum(fn (ServiceOrder $order) => (float) $order->total_price);
        $monthlyCosts = $finishedOrdersThisMonth->sum(fn (ServiceOrder $order) => (float) $order->total_cost);

        $completedWithTime = $finishedOrdersThisMonth
            ->map(fn (ServiceOrder $order) => $order->trackedSeconds())
            ->filter(fn (int $seconds) => $seconds > 0);

        $averageRepairSeconds = (int) round($completedWithTime->avg() ?? 0);

        $budgetDecisionCounts = ServiceOrder::where('tenant_id', $tenantId)
            ->whereBetween('updated_at', [$monthStart, $monthEnd])
            ->whereIn('status', ['approved', 'rejected', 'finished'])
            ->selectRaw("
                SUM(CASE WHEN status IN ('approved', 'finished') THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                COUNT(*) as total_count
            ")
            ->first();

        $approvedBudgets = (int) ($budgetDecisionCounts->approved_count ?? 0);
        $rejectedBudgets = (int) ($budgetDecisionCounts->rejected_count ?? 0);
        $budgetDecisions = (int) ($budgetDecisionCounts->total_count ?? 0);
        $approvalRate = $budgetDecisions > 0
            ? round(($approvedBudgets / $budgetDecisions) * 100, 1)
            : 0.0;

        $criticalItems = Item::forTenant($tenantId)
            ->whereColumn('stock_quantity', '<=', 'min_stock_alert')
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->get();

        $openOrdersCount = ServiceOrder::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['finished', 'rejected'])
            ->count();

        $overdueOrdersCount = ServiceOrder::where('tenant_id', $tenantId)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<', now())
            ->whereNotIn('status', ['finished', 'rejected'])
            ->count();

        $dailyNetRevenueSeries = $this->buildDailyNetRevenueSeries($monthStart, $monthEnd, $finishedOrdersThisMonth);

        return view('tenant.dashboard.index', compact(
            'approvalRate',
            'approvedBudgets',
            'averageRepairSeconds',
            'budgetDecisions',
            'criticalItems',
            'dailyNetRevenue',
            'dailyNetRevenueSeries',
            'month',
            'monthlyCosts',
            'monthlyGrossRevenue',
            'monthlyNetRevenue',
            'openOrdersCount',
            'overdueOrdersCount',
            'rejectedBudgets'
        ));
    }

    public function blocked()
    {
        return view('tenant.plan.blocked');
    }

    public function billing()
    {
        return view('tenant.billing.index', ['tenant' => Auth::user()->tenant]);
    }

    private function buildDailyNetRevenueSeries($monthStart, $monthEnd, $finishedOrders): array
    {
        $ordersByDay = $finishedOrders->groupBy(fn (ServiceOrder $order) => $order->updated_at->toDateString());
        $series = [];

        for ($date = $monthStart->copy(); $date->lte($monthEnd); $date->addDay()) {
            $orders = $ordersByDay->get($date->toDateString(), collect());

            $series[] = [
                'date' => $date->copy(),
                'value' => $orders->sum(fn (ServiceOrder $order) => (float) $order->total_price - (float) $order->total_cost),
            ];
        }

        return $series;
    }
}
