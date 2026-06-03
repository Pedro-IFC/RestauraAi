<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RestauraAí') }} - @yield('title', 'Painel')</title>

    <script src="https://cdn.tailwindcss.com"></script>

    @stack('styles')
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">

    <div class="min-h-screen flex flex-col">

        <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">

                    <div class="shrink-0 flex items-center">
                        <a href="/" class="text-2xl font-extrabold text-blue-600">
                            RestauraAí
                        </a>
                    </div>

                    <div class="hidden md:flex items-center gap-6">
                        <a href="{{ route('planos.index') }}" class="text-sm font-semibold {{ request()->routeIs('planos.*') ? 'text-blue-700' : 'text-gray-600 hover:text-blue-600' }}">
                            Planos
                        </a>
                        <a href="{{ route('admin.tenants.index') }}" class="text-sm font-semibold {{ request()->routeIs('admin.tenants.*') ? 'text-blue-700' : 'text-gray-600 hover:text-blue-600' }}">
                            Assistências
                        </a>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="flex items-center">
                        @csrf
                        <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <main id="topo" class="flex-grow">
            <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4">
                        <div class="flex">
                            <div class="ml-3">
                                <p class="text-sm text-red-700">Por favor, corrija os seguintes erros:</p>
                                <ul class="list-disc pl-5 text-sm text-red-700 mt-2">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @yield('content')

            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-500">
                    &copy; {{ date('Y') }} RestauraAí. Todos os direitos reservados.
                </p>
                <div class="flex space-x-6">
                    <a href="#" class="text-sm text-gray-400 hover:text-gray-600">Termos de Uso</a>
                    <a href="#" class="text-sm text-gray-400 hover:text-gray-600">Privacidade</a>
                </div>
            </div>
        </footer>

    </div>

    @stack('scripts')
</body>
</html>
