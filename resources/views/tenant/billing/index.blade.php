@extends('layouts.app')

@section('title', 'Regularizar assinatura')

@section('content')
    <div class="mx-auto max-w-2xl rounded-lg border border-red-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-700">
            <span class="text-xl font-bold">!</span>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Assinatura bloqueada</h1>
        <p class="mt-3 text-gray-600">
            O painel interno está temporariamente bloqueado por status de assinatura {{ strtolower($tenant->statusLabel()) }}.
            O catálogo público também ficará fora do ar até a regularização.
        </p>

        @if ($tenant->payment_overdue_since)
            <div class="mt-5 rounded-lg bg-red-50 p-4 text-sm text-red-800">
                Pagamento em atraso desde {{ $tenant->payment_overdue_since->format('d/m/Y') }}.
                Carência configurada: {{ $tenant->payment_grace_days }} dia(s).
            </div>
        @endif

        <p class="mt-5 text-sm text-gray-500">
            Entre em contato com o suporte do RestauraAí para regularizar a cobrança e reativar o acesso.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Sair
            </button>
        </form>
    </div>
@endsection
