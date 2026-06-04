@extends('layouts.app')

@section('title', $tenant->name)

@section('content')
    @php
        $customization = $tenant->customization;
        $canShowAdvancedCustomization = $tenant->hasFeature('customization_advanced');
        $primaryColor = $customization?->primary_color ?? '#2563eb';
        $secondaryColor = $customization?->secondary_color ?? '#f3f4f6';
        $banners = $canShowAdvancedCustomization ? ($customization?->banners ?? []) : [];
    @endphp

    <div class="mb-8 overflow-hidden rounded-lg bg-white shadow-sm">
        @if (filled($banners))
            <div class="grid gap-1 md:grid-cols-{{ min(count($banners), 3) }}">
                @foreach (array_slice($banners, 0, 3) as $banner)
                    <img src="{{ asset('storage/'.$banner) }}" alt="Banner {{ $tenant->name }}" class="h-56 w-full object-cover">
                @endforeach
            </div>
        @endif

        <div class="p-6" style="background: {{ $secondaryColor }}">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide" style="color: {{ $primaryColor }}">Catálogo público</p>
                    <div class="mt-3 flex items-center gap-4">
                        @if ($canShowAdvancedCustomization && $customization?->logo)
                            <img src="{{ asset('storage/'.$customization->logo) }}" alt="Logo {{ $tenant->name }}" class="max-h-16 max-w-40 object-contain">
                        @endif
                        <h1 class="text-3xl font-bold text-gray-900">{{ $tenant->name }}</h1>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if (auth()->user()?->isCustomer())
                        <a href="{{ route('public.account.index', $tenant->slug) }}"
                            class="w-fit rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Minha conta
                        </a>
                    @else
                        <a href="{{ route('public.customer.login', $tenant->slug) }}"
                            class="w-fit rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Entrar
                        </a>
                    @endif
                    <a href="{{ route('public.os.create', $tenant->slug) }}"
                        class="w-fit rounded-lg px-4 py-2 text-sm font-semibold text-white"
                        style="background: {{ $primaryColor }}">
                        Abrir chamado
                    </a>
                    <a href="{{ route('public.tracking.index', $tenant->slug) }}"
                        class="w-fit rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Acompanhar chamado
                    </a>
                    @if ($canShowAdvancedCustomization && $customization?->instagram_url)
                        <a href="{{ $customization->instagram_url }}" target="_blank" rel="noopener noreferrer"
                            class="w-fit rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Instagram
                        </a>
                    @endif
                </div>
            </div>

            @if ($customization?->about_text)
                <div class="mt-5 max-w-4xl whitespace-pre-line text-gray-700">{{ $customization->about_text }}</div>
            @endif

            @if ($customization?->address_text)
                <div class="mt-5 rounded-lg bg-white/70 p-4 text-sm text-gray-700">
                    <span class="font-semibold text-gray-900">Endereço:</span>
                    <span class="whitespace-pre-line">{{ $customization->address_text }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($items as $item)
            <article class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-gray-900">{{ $item->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ number_format((float) $item->stock_quantity, 2, ',', '.') }} disponível</p>
                    </div>
                    <div class="text-right text-lg font-bold text-gray-900">R$ {{ number_format((float) $item->sale_price, 2, ',', '.') }}</div>
                </div>
                <form method="POST" action="{{ route('public.checkout.cart.add', $tenant->slug) }}" class="mt-5 flex gap-2">
                    @csrf
                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                    <input name="quantity" type="number" min="1" step="1" value="1"
                        class="w-24 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background: {{ $primaryColor }}">
                        Adicionar
                    </button>
                </form>
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center text-gray-500 sm:col-span-2 lg:col-span-3">
                Nenhum produto disponível no momento.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $items->links() }}
    </div>

    @if ($canShowAdvancedCustomization && $customization?->google_maps_embed_src)
        <section class="mt-8 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">Localização</h2>
            </div>
            <iframe
                src="{{ $customization->google_maps_embed_src }}"
                class="h-80 w-full border-0"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"></iframe>
        </section>
    @endif
@endsection
