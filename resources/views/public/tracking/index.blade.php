@extends('layouts.app')

@section('title', 'Acompanhar chamado')

@section('content')
    <div class="mx-auto max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Acompanhar chamado</h1>
        <p class="mt-1 text-sm text-gray-600">Consulte pelo número do chamado ou CPF informado na abertura.</p>

        <form method="POST" action="{{ route('public.tracking.show', $tenant->slug) }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="lookup" class="block text-sm font-medium text-gray-700">CPF ou número do chamado</label>
                <input id="lookup" name="lookup" type="text" value="{{ old('lookup') }}" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('lookup')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between">
                <a href="{{ route('public.store.index', $tenant->slug) }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">
                    Voltar para a página da assistência
                </a>
                <button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Consultar
                </button>
            </div>
        </form>
    </div>
@endsection
