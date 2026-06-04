@extends('layouts.app')

@section('title', 'Código de acesso - '.$tenant->name)

@section('content')
    <div class="mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
        <div class="mb-6 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Digite o código</h1>
            <p class="mt-2 text-sm text-gray-600">Enviamos um código de 6 dígitos para seu e-mail.</p>
        </div>

        <form method="POST" action="{{ route('public.customer.magic.verify.submit', $tenant->slug) }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="code" class="block text-sm font-semibold text-gray-700">Código</label>
                <input id="code" name="code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required
                    class="mt-2 w-full rounded-lg border-gray-300 text-center text-2xl font-bold tracking-wider shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('code')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">
                Confirmar acesso
            </button>
        </form>

        <form method="POST" action="{{ route('public.customer.magic.request', $tenant->slug) }}" class="mt-4">
            @csrf
            <input type="hidden" name="email" value="{{ old('email') }}">
            <button class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Reenviar código
            </button>
        </form>
    </div>
@endsection
