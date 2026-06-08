<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrder;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimeTrackingController extends Controller
{
    public function start(Request $request, $id)
    {
        $serviceOrder = ServiceOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $alreadyRunning = TimeEntry::where('service_order_id', $serviceOrder->id)
            ->where('user_id', Auth::id())
            ->running()
            ->exists();

        if ($alreadyRunning) {
            return redirect()
                ->back()
                ->with('success', 'Cronômetro já está em execução nesta OS.');
        }

        TimeEntry::create([
            'service_order_id' => $serviceOrder->id,
            'user_id' => Auth::id(),
            'started_at' => now(),
            'duration_seconds' => 0,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Cronômetro iniciado.');
    }

    public function stop(Request $request, $id)
    {
        $serviceOrder = ServiceOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $timeEntry = TimeEntry::where('service_order_id', $serviceOrder->id)
            ->where('user_id', Auth::id())
            ->running()
            ->latest('started_at')
            ->first();

        if (! $timeEntry) {
            return redirect()
                ->back()
                ->withErrors(['time_tracking' => 'Não há cronômetro em execução nesta OS para o técnico atual.']);
        }

        $endedAt = now();

        $timeEntry->update([
            'ended_at' => $endedAt,
            'duration_seconds' => (int) $timeEntry->started_at->diffInSeconds($endedAt),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Cronômetro pausado.');
    }

    public function storeManual(Request $request, $id)
    {
        $serviceOrder = ServiceOrder::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $validated = $request->validate([
            'started_at' => 'required|date',
            'hours' => 'nullable|integer|min:0|max:999',
            'minutes' => 'nullable|integer|min:0|max:59',
        ]);

        $hours = (int) ($validated['hours'] ?? 0);
        $minutes = (int) ($validated['minutes'] ?? 0);
        $durationSeconds = ($hours * 3600) + ($minutes * 60);

        if ($durationSeconds <= 0) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['duration' => 'Informe uma duração maior que zero.']);
        }

        $startedAt = \Illuminate\Support\Carbon::parse($validated['started_at']);

        TimeEntry::create([
            'service_order_id' => $serviceOrder->id,
            'user_id' => Auth::id(),
            'started_at' => $startedAt,
            'ended_at' => $startedAt->copy()->addSeconds($durationSeconds),
            'duration_seconds' => $durationSeconds,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Tempo manual adicionado.');
    }
}
