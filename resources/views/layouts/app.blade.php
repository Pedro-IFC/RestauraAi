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
            --brand-yellow-strong: #f5b400;
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

        .brand-primary-button {
            background: var(--brand-yellow);
            color: var(--brand-ink);
            border: 1px solid rgba(17, 24, 39, 0.16);
            box-shadow: 0 0.25rem 0 var(--brand-orange);
        }

        .brand-primary-button:hover {
            background: var(--brand-yellow-strong);
        }

        .brand-link:hover {
            color: var(--brand-orange);
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
    @php
        $currentUser = auth()->user();
        $tenant = $currentUser?->tenant;
        $isCustomerUser = $currentUser?->isCustomer();
        $isTenantUser = $currentUser && $tenant && ! $isCustomerUser && $currentUser->role !== 'superadmin';
        $publicSlug = request()->route('slug') ?? session('public_tenant_slug') ?? ($isCustomerUser ? $tenant?->slug : null);
        $tenantMenuItems = [];
        $customerMenuItems = [];
        $brandUrl = $isTenantUser ? route('tenant.dashboard') : url('/');

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

        if ($isCustomerUser && $publicSlug) {
            $brandUrl = route('public.account.index', $publicSlug);
            $customerMenuItems = [
                ['label' => 'Loja', 'route' => 'public.store.index', 'active' => request()->routeIs('public.store.index')],
                ['label' => 'Minha conta', 'route' => 'public.account.index', 'active' => request()->routeIs('public.account.*')],
                ['label' => 'Novo chamado', 'route' => 'public.os.create', 'active' => request()->routeIs('public.os.*')],
                ['label' => 'Acompanhamento', 'route' => 'public.tracking.index', 'active' => request()->routeIs('public.tracking.*')],
                ['label' => 'Carrinho', 'route' => 'public.checkout.cart', 'active' => request()->routeIs('public.checkout.*')],
            ];
        }
    @endphp

    <div class="min-h-screen flex flex-col">
        @if($isTenantUser || ($currentUser && $currentUser->role == 'superadmin'))
            <nav class="sticky top-0 z-50 border-b border-yellow-200 bg-white shadow-sm">
                <div class="brand-stripe-bar"></div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">

                        <div class="shrink-0 flex items-center">
                            <a href="{{ $brandUrl }}" class="brand-wordmark text-2xl font-extrabold">
                                RestauraAí
                            </a>
                        </div>

                        <div class="hidden md:flex items-center space-x-6">
                            @if ($isTenantUser)
                                @foreach ($tenantMenuItems as $item)
                                    <a href="{{ route($item['route']) }}"
                                        class="text-sm font-semibold transition {{ $item['active'] ? 'text-orange-600' : 'brand-link text-gray-600' }}">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                                @if ($tenant?->slug)
                                    <a href="{{ url('/'.$tenant->slug) }}" class="brand-link text-sm font-semibold text-gray-600 transition">
                                        Microssite
                                    </a>
                                @endif
                            @elseif ($isCustomerUser)
                                @foreach ($customerMenuItems as $item)
                                    <a href="{{ route($item['route'], $publicSlug) }}"
                                        class="text-sm font-semibold transition {{ $item['active'] ? 'text-orange-600' : 'brand-link text-gray-600' }}">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
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
                            @elseif ($isCustomerUser)
                                <div class="hidden text-right sm:block">
                                    <div class="text-sm font-semibold text-gray-900">{{ $currentUser->name }}</div>
                                    <div class="text-xs text-gray-500">Cliente</div>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                        Sair
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('tenant.dashboard') }}" class="brand-primary-button rounded-lg px-5 py-2 text-sm font-bold shadow-md transition transform hover:-translate-y-0.5">
                                    Acessar Painel
                                </a>
                            @endif
                        </div>

                    </div>

                    @if ($isTenantUser)
                        <div class="flex gap-2 overflow-x-auto border-t border-gray-100 py-3 md:hidden">
                            @foreach ($tenantMenuItems as $item)
                                <a href="{{ route($item['route']) }}"
                                    class="shrink-0 rounded-full px-3 py-1.5 text-sm font-semibold {{ $item['active'] ? 'bg-yellow-400 text-gray-950' : 'bg-gray-100 text-gray-700' }}">
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

                    @if ($isCustomerUser && $customerMenuItems)
                        <div class="flex gap-2 overflow-x-auto border-t border-gray-100 py-3 md:hidden">
                            @foreach ($customerMenuItems as $item)
                                <a href="{{ route($item['route'], $publicSlug) }}"
                                    class="shrink-0 rounded-full px-3 py-1.5 text-sm font-semibold {{ $item['active'] ? 'bg-yellow-400 text-gray-950' : 'bg-gray-100 text-gray-700' }}">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </nav>
        @endif
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
