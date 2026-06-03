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
    @php
        $currentUser = auth()->user();
        $tenant = $currentUser?->tenant;
        $isTenantUser = $currentUser && $tenant && $currentUser->role !== 'superadmin';
        $tenantMenuItems = [];

        if ($isTenantUser) {
            $tenantMenuItems[] = ['label' => 'Início', 'route' => 'tenant.dashboard', 'active' => request()->routeIs('tenant.dashboard')];

            if ($tenant->hasFeature('kanban')) {
                $tenantMenuItems[] = ['label' => 'Kanban', 'route' => 'tenant.kanban.index', 'active' => request()->routeIs('tenant.kanban.*') || request()->routeIs('ordens-servico.*')];
            }

            if ($tenant->hasFeature('inventory')) {
                $tenantMenuItems[] = ['label' => 'Estoque', 'route' => 'estoque.index', 'active' => request()->routeIs('estoque.*')];
            }

            if ($tenant->hasFeature('schedule')) {
                $tenantMenuItems[] = ['label' => 'Agenda', 'route' => 'tenant.schedule.index', 'active' => request()->routeIs('tenant.schedule.*')];
            }

            if ($tenant->hasFeature('customization_basic')) {
                $tenantMenuItems[] = ['label' => 'Customização', 'route' => 'tenant.customization.edit', 'active' => request()->routeIs('tenant.customization.*')];
            }
        }
    @endphp

    <div class="min-h-screen flex flex-col">

        <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">

                    <div class="shrink-0 flex items-center">
                        <a href="{{ $isTenantUser ? route('tenant.dashboard') : url('/') }}" class="text-2xl font-extrabold text-blue-600">
                            RestauraAí
                        </a>
                    </div>

                    <div class="hidden md:flex items-center space-x-6">
                        @if ($isTenantUser)
                            @foreach ($tenantMenuItems as $item)
                                <a href="{{ route($item['route']) }}"
                                    class="text-sm font-semibold transition {{ $item['active'] ? 'text-blue-700' : 'text-gray-600 hover:text-blue-600' }}">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                            @if ($tenant?->slug)
                                <a href="{{ url('/'.$tenant->slug) }}" class="text-sm font-semibold text-gray-600 transition hover:text-blue-600">
                                    Microssite
                                </a>
                            @endif
                        @else
                            <a href="#topo" class="text-sm font-medium text-gray-600 hover:text-blue-600 transition">Início</a>
                            <a href="#recursos" class="text-sm font-medium text-gray-600 hover:text-blue-600 transition">Recursos</a>
                            <a href="#vantagens" class="text-sm font-medium text-gray-600 hover:text-blue-600 transition">Vantagens</a>
                            <a href="#contato" class="text-sm font-medium text-gray-600 hover:text-blue-600 transition">Contato</a>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        @if ($isTenantUser)
                            <div class="hidden text-right sm:block">
                                <div class="text-sm font-semibold text-gray-900">{{ $tenant->name }}</div>
                                <div class="text-xs text-gray-500">{{ $tenant->plan?->name ?? 'Sem plano' }}</div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    Sair
                                </button>
                            </form>
                        @else
                            <a href="{{ route('tenant.dashboard') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-semibold shadow-md transition transform hover:-translate-y-0.5">
                                Acessar Painel
                            </a>
                        @endif
                    </div>

                </div>

                @if ($isTenantUser)
                    <div class="flex gap-2 overflow-x-auto border-t border-gray-100 py-3 md:hidden">
                        @foreach ($tenantMenuItems as $item)
                            <a href="{{ route($item['route']) }}"
                                class="shrink-0 rounded-full px-3 py-1.5 text-sm font-semibold {{ $item['active'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                        @if ($tenant?->slug)
                            <a href="{{ url('/'.$tenant->slug) }}" class="shrink-0 rounded-full bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-700">
                                Microssite
                            </a>
                        @endif
                    </div>
                @endif
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
