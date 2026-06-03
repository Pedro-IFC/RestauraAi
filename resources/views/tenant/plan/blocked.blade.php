@extends('layouts.app')

@section('title', 'Recurso bloqueado')

@section('content')
    <div class="mx-auto max-w-2xl rounded-lg border border-amber-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <span class="text-xl font-bold">!</span>
        </div>
        <h1 class="mt-4 text-2xl font-bold text-gray-900">Recurso não disponível no plano atual</h1>
        <p class="mt-3 text-gray-600">
            Esta funcionalidade depende de liberação no plano contratado pela assistência. Fale com o superadmin para revisar a assinatura.
        </p>

        <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
            @if (auth()->user()->tenant?->hasFeature('kanban'))
                <a href="{{ route('tenant.kanban.index') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Ir para Kanban
                </a>
            @endif
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('blocked-logout-form').submit();" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Sair
            </a>
        </div>

        <form id="blocked-logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>
    </div>
@endsection
