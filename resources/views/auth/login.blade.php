@extends('layouts.app')

@section('content')
    <div class="bg-gray-100 flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 m-4 border border-gray-200">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-blue-600">RestauraAí</h1>
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
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="seu@email.com">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">Senha</label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        id="password" type="password" name="password" required placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Lembrar de mim</span>
                    </label>
                    <a href="#" class="text-sm text-blue-600 hover:underline">Esqueceu a senha?</a>
                </div>

                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg shadow transition duration-200" type="submit">
                    Entrar no Painel
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Ainda não tem uma conta? <a href="/#planos" class="text-blue-600 hover:underline font-semibold">Crie agora</a>
                </p>
            </div>

        </div>
    </div>
@endsection
