@extends('layouts.app')

@section('title', 'Editar item')

@section('content')
    <div class="mx-auto max-w-3xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">Editar item</h1>
        <p class="mt-1 text-sm text-gray-600">Atualize destino, preços e posição de estoque.</p>

        <form method="POST" action="{{ route('estoque.update', $item) }}" enctype="multipart/form-data" class="mt-6">
            @include('tenant.items.partials.form', ['item' => $item])
        </form>
    </div>
@endsection
