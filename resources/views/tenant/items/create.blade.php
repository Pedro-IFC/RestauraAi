@extends('layouts.app')

@section('title', 'Novo item')

@section('content')
    <div class="mx-auto max-w-3xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-900">Novo item</h1>
        <p class="mt-1 text-sm text-gray-600">Cadastre produtos de venda ou insumos internos usados na bancada.</p>

        <form method="POST" action="{{ route('estoque.store') }}" enctype="multipart/form-data" class="mt-6">
            @include('tenant.items.partials.form', ['item' => new \App\Models\Item()])
        </form>
    </div>
@endsection
