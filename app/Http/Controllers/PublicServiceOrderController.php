<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\KanbanColumn;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicServiceOrderController extends Controller
{
    private const DEFAULT_COLUMNS = [
        'Triagem',
        'Orçamento',
        'Aguardando Peça',
        'Na Bancada',
        'Testes',
        'Pronto',
    ];

    public function create(Request $request, $slug)
    {
        $tenant = $this->publicTenant($slug);

        abort_unless($tenant->hasFeature('kanban'), 404);

        return view('public.service_order.create', compact('tenant'));
    }

    public function store(Request $request, $slug)
    {
        $tenant = $this->publicTenant($slug);

        abort_unless($tenant->hasFeature('kanban'), 404);

        if ($tenant->hasReachedMonthlyServiceOrderLimit()) {
            return back()
                ->withInput()
                ->withErrors(['plan' => 'Esta assistência atingiu o limite mensal de chamados do plano.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cpf' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'device_model' => 'required|string|max:255',
            'defect_symptoms' => 'required|string',
            'attachments' => 'nullable|array|max:6',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:20480',
        ]);

        $attachments = collect($request->file('attachments', []))
            ->map(fn ($file) => $file->store('service-orders/'.$tenant->id, 'public'))
            ->values()
            ->all();

        $serviceOrder = DB::transaction(function () use ($tenant, $validated, $attachments) {
            $this->ensureDefaultColumns($tenant->id);

            $customerData = [
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
            ];

            $customer = filled($validated['cpf'])
                ? Customer::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'cpf' => $validated['cpf'],
                    ],
                    $customerData
                )
                : Customer::create([
                    'tenant_id' => $tenant->id,
                    'cpf' => null,
                    'name' => $validated['name'],
                    'phone' => $validated['phone'] ?? null,
                    'email' => $validated['email'] ?? null,
                ]);

            $firstColumn = KanbanColumn::where('tenant_id', $tenant->id)
                ->orderBy('order_index')
                ->firstOrFail();

            return ServiceOrder::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'kanban_column_id' => $firstColumn->id,
                'kanban_position' => (ServiceOrder::where('tenant_id', $tenant->id)
                    ->where('kanban_column_id', $firstColumn->id)
                    ->max('kanban_position') ?? 0) + 1,
                'device_model' => $validated['device_model'],
                'defect_symptoms' => $validated['defect_symptoms'],
                'attachments' => $attachments,
                'status' => 'pending',
                'total_cost' => 0,
                'total_price' => 0,
            ]);
        });

        return redirect()
            ->to('/'.$tenant->slug)
            ->with('success', 'Chamado #'.$serviceOrder->id.' aberto. Use este número para acompanhar o andamento.');
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

    private function publicTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        $tenant->enforceSubscriptionLifecycle();

        abort_unless($tenant->isPubliclyAvailable(), 404);

        return $tenant;
    }
}
