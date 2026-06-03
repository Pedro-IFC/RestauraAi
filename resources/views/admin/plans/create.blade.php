@extends('layouts.app')

@section('title', 'Novo Plano')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Superadmin</p>
            <h1 class="text-3xl font-bold text-gray-950">Novo plano</h1>
            <p class="mt-1 text-sm text-gray-600">Defina preços, trial e limites técnicos para a assinatura.</p>
        </div>

        <form method="POST" action="{{ route('planos.store') }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @include('admin.plans.partials.form')
        </form>
    </div>
@endsection
