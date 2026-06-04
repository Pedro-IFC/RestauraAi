@php
    $canDecideBudget = (float) $order->total_price > 0 && ! in_array($order->status, ['approved', 'rejected', 'finished'], true);
    $statusLabel = [
        'pending' => 'Pendente',
        'budgeting' => 'Orçamento',
        'approved' => 'Orçamento aprovado',
        'rejected' => 'Orçamento recusado',
        'finished' => 'Finalizado',
    ][$order->status] ?? $order->status;
@endphp

<article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-900">Chamado #{{ $order->id }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ $order->device_model }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">
                {{ $order->kanbanColumn?->name ?? $statusLabel }}
            </span>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700">
                {{ $statusLabel }}
            </span>
        </div>
    </div>

    <dl class="mt-5 grid gap-4 md:grid-cols-3">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Entrada</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Prazo prometido</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $order->deadline_at?->format('d/m/Y H:i') ?? 'A definir' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Orçamento</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">
                {{ (float) $order->total_price > 0 ? 'R$ '.number_format((float) $order->total_price, 2, ',', '.') : 'Aguardando análise' }}
            </dd>
        </div>
    </dl>

    <div class="mt-5 rounded-lg bg-gray-50 p-4">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Sintomas informados</div>
        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $order->defect_symptoms }}</p>
    </div>

    @if (filled($order->attachments ?? []))
        <div class="mt-5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Anexos</div>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($order->attachments as $attachment)
                    <a href="{{ asset('storage/'.$attachment) }}" target="_blank" rel="noopener noreferrer"
                        class="rounded-full bg-gray-100 px-3 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                        Arquivo {{ $loop->iteration }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if ($order->budget_decided_at)
        <p class="mt-4 text-sm text-gray-600">
            Resposta do orçamento registrada em {{ $order->budget_decided_at->format('d/m/Y H:i') }}.
        </p>
    @endif

    @if ($order->status === 'approved' && (float) $order->total_price > 0)
        <form method="POST" action="{{ route('public.checkout.budget', [$tenant->slug, $order->id]) }}" class="mt-5 border-t border-gray-200 pt-5">
            @csrf
            <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                <select name="payment_method" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="pix">Pix</option>
                    <option value="card">Cartão</option>
                </select>
                <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Criar pagamento em aberto
                </button>
            </div>
        </form>
    @endif

    <div class="mt-5 grid gap-4 border-t border-gray-200 pt-5 lg:grid-cols-[1fr_auto]">
        <form method="POST" action="{{ route('public.tracking.attachments.store', [$tenant->slug, $order->id]) }}" enctype="multipart/form-data" class="space-y-2">
            @csrf
            <label for="attachments_{{ $order->id }}" class="block text-sm font-semibold text-gray-700">Enviar fotos ou vídeos adicionais</label>
            <input id="attachments_{{ $order->id }}" name="attachments[]" type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/x-msvideo"
                class="block w-full rounded-lg border border-gray-300 text-sm text-gray-700 file:mr-4 file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Adicionar anexos
            </button>
        </form>

        @if ($canDecideBudget)
            <div class="flex flex-col gap-2 sm:flex-row lg:self-end">
                <form method="POST" action="{{ route('public.tracking.budget', [$tenant->slug, $order->id]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decision" value="rejected">
                    <button class="w-full rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 sm:w-auto">
                        Recusar orçamento
                    </button>
                </form>
                <form method="POST" action="{{ route('public.tracking.budget', [$tenant->slug, $order->id]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="decision" value="approved">
                    <button class="w-full rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 sm:w-auto">
                        Aprovar orçamento
                    </button>
                </form>
            </div>
        @endif
    </div>
</article>
