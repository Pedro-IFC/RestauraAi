@extends('layouts.app')

@section('title', 'Entrar - '.$tenant->name)

@section('content')
    <div class="mx-auto grid max-w-5xl gap-6 md:grid-cols-2">
        <section class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
            <div class="mb-6 text-center">
                <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">Acesso rápido</h1>
                <p class="mt-2 text-sm text-gray-600">Receba um código no e-mail e entre sem senha.</p>
            </div>

            <form method="POST" action="{{ route('public.customer.magic.request', $tenant->slug) }}" class="space-y-5">
                @csrf
                @if ($intended)
                    <input type="hidden" name="intended" value="{{ $intended }}">
                @endif

                <div>
                    <label for="magic_email" class="block text-sm font-semibold text-gray-700">E-mail</label>
                    <input id="magic_email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <button class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">
                    Receber código
                </button>
            </form>

            <p class="mt-4 text-xs text-gray-500">Se o e-mail ainda não tiver conta, criaremos uma conta de cliente automaticamente ao confirmar o código.</p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
        <div class="mb-6 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
            <h2 class="mt-2 text-2xl font-bold text-gray-900">Entrar com senha</h2>
            <p class="mt-2 text-sm text-gray-600">Use sua conta RestauraAí para ver o histórico desta assistência.</p>
        </div>

        <form method="POST" action="{{ route('public.customer.login.submit', $tenant->slug) }}" class="space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-gray-700">Senha</label>
                <input id="password" name="password" type="password" required
                    class="mt-2 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                Lembrar de mim
            </label>

            <button class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white hover:bg-blue-700">
                Entrar
            </button>
        </form>

        <p class="mt-6 text-center text-sm text-gray-600">
            Ainda não tem conta?
            <a href="{{ route('public.customer.register', $tenant->slug) }}" class="font-semibold text-blue-600 hover:underline">Criar cadastro</a>
        </p>
        </section>
    </div>
@endsection
