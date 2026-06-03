@extends('layouts.app')

@section('title', 'Editar Plano')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">Superadmin</p>
            <h1 class="text-3xl font-bold text-gray-950">Editar plano</h1>
            <p class="mt-1 text-sm text-gray-600">Atualize preços, trial e limites técnicos de {{ $plan->name }}.</p>
        </div>

        <form method="POST" action="{{ route('planos.update', $plan) }}" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')
            @include('admin.plans.partials.form')
        </form>
    </div>
@endsection
