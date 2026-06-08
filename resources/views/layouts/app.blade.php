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
            --brand-base: #f8fafc;
            --brand-surface: #ffffff;
            --brand-border: #e2e8f0;
            --brand-muted: #64748b;
            --brand-ink: #0f172a;
            --brand-secondary: #fef3c7;
            --brand-secondary-strong: #fde68a;
            --brand-accent: #f97316;
            --brand-accent-strong: #ea580c;
            --brand-stripe: repeating-linear-gradient(135deg, #0f172a 0 10px, #ffffff 10px 20px);
        }

        .brand-wordmark {
            color: var(--brand-ink);
            letter-spacing: 0;
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
                var(--brand-secondary-strong);
            box-shadow: 0 0.28rem 0 var(--brand-accent);
        }

        .brand-stripe-bar {
            height: 0.2rem;
            background: linear-gradient(90deg, var(--brand-ink) 0 60%, var(--brand-secondary-strong) 60% 90%, var(--brand-accent) 90% 100%);
        }

        .brand-primary-button {
            background: var(--brand-accent);
            color: #ffffff;
            border: 1px solid rgba(234, 88, 12, 0.28);
            box-shadow: 0 0.18rem 0 var(--brand-accent-strong);
        }

        .brand-primary-button:hover {
            background: var(--brand-accent-strong);
        }

        .brand-link:hover {
            color: var(--brand-accent);
        }

        .brand-shell {
            background:
                linear-gradient(180deg, rgba(254, 243, 199, 0.72), rgba(248, 250, 252, 0.96) 18rem),
                var(--brand-base);
        }

        .brand-nav {
            border-color: var(--brand-border);
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(14px);
        }

        .text-blue-600,
        .text-blue-700,
        .hover\:text-blue-600:hover,
        .hover\:text-blue-700:hover {
            color: var(--brand-accent) !important;
        }

        .bg-blue-50 {
            background-color: var(--brand-secondary) !important;
        }

        .bg-blue-100 {
            background-color: var(--brand-secondary-strong) !important;
        }

        .bg-blue-600,
        .hover\:bg-blue-700:hover,
        .hover\:bg-blue-500:hover {
            background-color: var(--brand-accent) !important;
            color: #ffffff !important;
        }

        .border-blue-100,
        .border-blue-200,
        .border-blue-300,
        .hover\:border-blue-300:hover,
        .hover\:border-blue-500:hover {
            border-color: var(--brand-secondary-strong) !important;
        }

        .focus\:border-blue-500:focus {
            border-color: var(--brand-accent) !important;
        }
    </style>

    @stack('styles')
</head>
<body class="brand-shell font-sans antialiased text-gray-900">
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

            if ($tenant->hasFeature('catalog')) {
                $tenantMenuItems[] = ['label' => 'Pedidos', 'route' => 'tenant.checkout-orders.index', 'active' => request()->routeIs('tenant.checkout-orders.*')];
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
        @if($isTenantUser || $isCustomerUser || ($currentUser && $currentUser->role == 'superadmin'))
            <nav class="brand-nav sticky top-0 z-50 border-b shadow-sm">
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
                                        class="text-sm font-semibold transition {{ $item['active'] ? 'text-orange-600' : 'brand-link text-slate-600' }}">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                                @if ($tenant?->slug)
                                    <a href="{{ url('/'.$tenant->slug) }}" class="brand-link text-sm font-semibold text-slate-600 transition">
                                        Microssite
                                    </a>
                                @endif
                            @elseif ($isCustomerUser)
                                @foreach ($customerMenuItems as $item)
                                    <a href="{{ route($item['route'], $publicSlug) }}"
                                        class="text-sm font-semibold transition {{ $item['active'] ? 'text-orange-600' : 'brand-link text-slate-600' }}">
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
                                    <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
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
                                    <button class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
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
                                    class="shrink-0 rounded-full px-3 py-1.5 text-sm font-semibold {{ $item['active'] ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                            @if ($tenant?->slug)
                                <a href="{{ url('/'.$tenant->slug) }}" class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700">
                                    Microssite
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($isCustomerUser && $customerMenuItems)
                        <div class="flex gap-2 overflow-x-auto border-t border-gray-100 py-3 md:hidden">
                            @foreach ($customerMenuItems as $item)
                                <a href="{{ route($item['route'], $publicSlug) }}"
                                    class="shrink-0 rounded-full px-3 py-1.5 text-sm font-semibold {{ $item['active'] ? 'bg-orange-500 text-white' : 'bg-slate-100 text-slate-700' }}">
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

        <footer class="mt-auto border-t border-slate-200 bg-white/90">
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
