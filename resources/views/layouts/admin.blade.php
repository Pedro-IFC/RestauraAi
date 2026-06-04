<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'RestauraAí') }} - @yield('title', 'Painel')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --brand-yellow: #facc15;
            --brand-orange: #f97316;
            --brand-ink: #111827;
            --brand-stripe: repeating-linear-gradient(135deg, #111827 0 10px, #ffffff 10px 20px);
        }

        .brand-wordmark {
            color: var(--brand-ink);
            letter-spacing: -0.03em;
        }

        .brand-wordmark::before {
            content: "";
            display: inline-block;
            width: 1.65rem;
            height: 1.65rem;
            margin-right: 0.55rem;
            vertical-align: -0.25rem;
            border: 2px solid var(--brand-ink);
            border-radius: 0.45rem 0.45rem 0.7rem 0.7rem;
            background:
                linear-gradient(90deg, transparent 0 33%, #ffffff 33% 42%, transparent 42% 58%, #ffffff 58% 67%, transparent 67%),
                var(--brand-yellow);
            box-shadow: 0 0.35rem 0 var(--brand-orange);
        }

        .brand-stripe-bar {
            height: 0.35rem;
            background: var(--brand-stripe);
        }

        .text-blue-600,
        .text-blue-700,
        .hover\:text-blue-600:hover,
        .hover\:text-blue-700:hover {
            color: var(--brand-orange) !important;
        }

        .bg-blue-50 {
            background-color: #fffbeb !important;
        }

        .bg-blue-100 {
            background-color: #fef3c7 !important;
        }

        .bg-blue-600,
        .hover\:bg-blue-700:hover,
        .hover\:bg-blue-500:hover {
            background-color: var(--brand-yellow) !important;
            color: var(--brand-ink) !important;
        }

        .border-blue-100,
        .border-blue-200,
        .border-blue-300,
        .hover\:border-blue-300:hover,
        .hover\:border-blue-500:hover {
            border-color: #fde68a !important;
        }

        .focus\:border-blue-500:focus {
            border-color: var(--brand-orange) !important;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-yellow-50/40 font-sans antialiased text-gray-900">

    <div class="min-h-screen flex flex-col">

        <nav class="sticky top-0 z-50 border-b border-yellow-200 bg-white shadow-sm">
            <div class="brand-stripe-bar"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">

                    <div class="shrink-0 flex items-center">
                        <a href="/" class="brand-wordmark text-2xl font-extrabold">
                            RestauraAí
                        </a>
                    </div>

                    <div class="hidden md:flex items-center gap-6">
                        <a href="{{ route('planos.index') }}" class="text-sm font-semibold {{ request()->routeIs('planos.*') ? 'text-orange-600' : 'text-gray-600 hover:text-orange-600' }}">
                            Planos
                        </a>
                        <a href="{{ route('admin.tenants.index') }}" class="text-sm font-semibold {{ request()->routeIs('admin.tenants.*') ? 'text-orange-600' : 'text-gray-600 hover:text-orange-600' }}">
                            Assistências
                        </a>
                        <a href="{{ route('admin.consumers.index') }}" class="text-sm font-semibold {{ request()->routeIs('admin.consumers.*') ? 'text-orange-600' : 'text-gray-600 hover:text-orange-600' }}">
                            Consumidores
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

        <footer class="mt-auto border-t border-yellow-200 bg-white">
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
