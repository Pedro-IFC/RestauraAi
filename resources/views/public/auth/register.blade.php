@extends('layouts.app')

@section('title', 'Cadastro - '.$tenant->name)

@section('content')
    <div class="mx-auto max-w-xl rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
        <div class="mb-6 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Criar conta de cliente</h1>
            <p class="mt-2 text-sm text-gray-600">Seu login será único e poderá ser usado em outras assistências.</p>
        </div>

        <form method="POST" action="{{ route('public.customer.register.submit', $tenant->slug) }}" class="grid gap-5 md:grid-cols-2">
            @csrf

            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-semibold text-gray-700">Nome completo</label>
                <input id="name" name="name" value="{{ old('name') }}" required
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2">
                <label for="email" class="block text-sm font-semibold text-gray-700">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="cpf" class="block text-sm font-semibold text-gray-700">CPF</label>
                <input id="cpf" name="cpf" value="{{ old('cpf') }}"
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700">Telefone</label>
                <input id="phone" name="phone" value="{{ old('phone') }}"
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700">Senha</label>
                <input id="password" name="password" type="password" required
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700">Confirmar senha</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div class="md:col-span-2">
                <button class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">
                    Criar conta
                </button>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Já tem conta?
            <a href="{{ route('public.customer.login', $tenant->slug) }}" class="font-semibold text-blue-600 hover:underline">Entrar</a>
        </p>
    </div>
@endsection
