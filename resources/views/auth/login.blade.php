@extends('layouts.app')

@section('content')
    <div class="flex min-h-screen items-center justify-center bg-yellow-50/40">
        <div class="m-4 w-full max-w-md overflow-hidden rounded-xl border border-yellow-200 bg-white shadow-lg">
            <div class="h-3 bg-[repeating-linear-gradient(135deg,#111827_0_10px,#ffffff_10px_20px)]"></div>
            <div class="p-8">
                <div class="text-center mb-8">
                    <h1 class="brand-wordmark text-3xl font-extrabold">RestauraAí</h1>
                    <p class="text-gray-500 mt-2 text-sm">Acesse o painel da sua assistência</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
                        <p class="text-sm text-red-700 font-medium">
                            {{ $errors->first() }}
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    <div class="mb-5">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">E-mail</label>
                        <input class="w-full rounded-lg border border-gray-300 px-4 py-3 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-orange-500"
                            id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="seu@email.com">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">Senha</label>
                        <input class="w-full rounded-lg border border-gray-300 px-4 py-3 transition focus:border-transparent focus:outline-none focus:ring-2 focus:ring-orange-500"
                            id="password" type="password" name="password" required placeholder="••••••••">
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                            <span class="ml-2 text-sm text-gray-600">Lembrar de mim</span>
                        </label>
                        <a href="#" class="text-sm font-semibold text-orange-600 hover:underline">Esqueceu a senha?</a>
                    </div>

                    <button class="w-full rounded-lg bg-yellow-300 px-4 py-3 font-extrabold text-gray-950 shadow-[0_4px_0_#f97316] transition duration-200 hover:bg-yellow-400" type="submit">
                        Entrar no Painel
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Ainda não tem uma conta? <a href="/#planos" class="font-semibold text-orange-600 hover:underline">Crie agora</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
