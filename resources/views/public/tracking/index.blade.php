@extends('layouts.app')

@section('title', 'Central de Ordens de Serviço')

@section('content')
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Central de Ordens de Serviço</h1>
            <p class="mt-1 text-sm text-gray-600">Ordens ativas e históricas vinculadas à sua conta nesta assistência.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('public.account.index', $tenant->slug) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Minha Conta
            </a>
            <a href="{{ route('public.os.create', $tenant->slug) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Abrir chamado
            </a>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($authenticatedOrders as $order)
            @include('public.tracking.partials.service-order-card', ['order' => $order, 'tenant' => $tenant])
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                Nenhuma ordem de serviço vinculada à sua conta nesta assistência.
            </div>
        @endforelse
    </div>
@endsection
