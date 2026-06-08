@extends('layouts.app')

@section('title', $tenant->name)

@section('content')
    @php
        $customization = $tenant->customization;
        $canShowAdvancedCustomization = $tenant->hasFeature('customization_advanced');
        $primaryColor = $customization?->primary_color ?? '#f97316';
        $secondaryColor = $customization?->secondary_color ?? '#fef3c7';
        $banners = $canShowAdvancedCustomization ? ($customization?->banners ?? []) : [];
        $viewer = auth()->user();
        $canUseCustomerActions = ! $viewer || $viewer->isCustomer();
    @endphp

    <div class="mb-8 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        @if (filled($banners))
            <div class="grid gap-1 md:grid-cols-{{ min(count($banners), 3) }}">
                @foreach (array_slice($banners, 0, 3) as $banner)
                    <img src="{{ asset('storage/'.$banner) }}" alt="Banner {{ $tenant->name }}" class="h-56 w-full object-cover">
                @endforeach
            </div>
        @endif

        <div class="h-2 bg-[linear-gradient(90deg,#0f172a_0_60%,#fde68a_60%_90%,#f97316_90%_100%)]"></div>
        <div class="p-6" style="background: linear-gradient(135deg, {{ $secondaryColor }} 0%, #ffffff 54%, #ffffff 100%)">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-extrabold uppercase tracking-wide text-slate-700">Catálogo público</p>
                    <div class="mt-3 flex items-center gap-4">
                        @if ($canShowAdvancedCustomization && $customization?->logo)
                            <img src="{{ asset('storage/'.$customization->logo) }}" alt="Logo {{ $tenant->name }}" class="max-h-16 max-w-40 object-contain">
                        @endif
                        <h1 class="text-3xl font-bold text-slate-950">{{ $tenant->name }}</h1>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($canUseCustomerActions)
                        @if ($viewer?->isCustomer())
                            <a href="{{ route('public.account.index', $tenant->slug) }}"
                                class="w-fit rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Minha conta
                            </a>
                        @else
                            <a href="{{ route('public.customer.login', $tenant->slug) }}"
                                class="w-fit rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                                Entrar
                            </a>
                        @endif
                        <a href="{{ route('public.os.create', $tenant->slug) }}"
                            class="w-fit rounded-lg border border-slate-900/10 px-4 py-2 text-sm font-extrabold text-slate-950 shadow-[0_3px_0_#ea580c]"
                            style="background: {{ $primaryColor }}">
                            Abrir chamado
                        </a>
                        <a href="{{ route('public.tracking.index', $tenant->slug) }}"
                            class="w-fit rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Acompanhar chamado
                        </a>
                    @endif
                    @if ($canShowAdvancedCustomization && $customization?->instagram_url)
                        <a href="{{ $customization->instagram_url }}" target="_blank" rel="noopener noreferrer"
                            class="w-fit rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Instagram
                        </a>
                    @endif
                </div>
            </div>

            @if ($viewer && ! $canUseCustomerActions)
                <div class="mt-5 rounded-lg bg-white/80 p-4 text-sm font-semibold text-slate-700">
                    Você está visualizando o microssite como usuário interno.
                </div>
            @endif

            @if ($customization?->about_text)
                <div class="mt-5 max-w-4xl whitespace-pre-line text-slate-700">{{ $customization->about_text }}</div>
            @endif

            @if ($customization?->address_text)
                <div class="mt-5 rounded-lg border border-slate-200 bg-white/84 p-4 text-sm text-slate-700">
                    <span class="font-semibold text-slate-950">Endereço:</span>
                    <span class="whitespace-pre-line">{{ $customization->address_text }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($items as $item)
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-orange-300">
                @php
                    $images = collect($item->images ?? [])->take(3);
                    $mainImage = $images->first();
                @endphp
                <div class="mb-4 overflow-hidden rounded-lg border border-slate-100 bg-slate-100">
                    @if ($mainImage)
                        <img src="{{ asset('storage/'.$mainImage) }}" alt="{{ $item->name }}" class="h-44 w-full object-cover" data-product-main-image="{{ $item->id }}">
                    @else
                        <div class="flex h-44 items-center justify-center bg-[linear-gradient(135deg,#f8fafc_0%,#fef3c7_100%)]">
                            <span class="rounded-full bg-white px-4 py-2 text-xs font-extrabold uppercase tracking-wide text-slate-800 shadow-sm ring-1 ring-slate-200">
                                Produto
                            </span>
                        </div>
                    @endif
                </div>
                @if ($images->count() > 1)
                    <div class="mb-4 grid grid-cols-3 gap-2">
                        @foreach ($images as $image)
                            <button type="button"
                                class="rounded-md border {{ $loop->first ? 'border-orange-500 ring-2 ring-orange-200' : 'border-slate-100' }} bg-white p-0.5 transition hover:border-orange-400"
                                data-product-thumbnail="{{ $item->id }}"
                                data-image-src="{{ asset('storage/'.$image) }}"
                                data-image-alt="{{ $item->name }} - imagem {{ $loop->iteration }}"
                                aria-label="Ver imagem {{ $loop->iteration }} de {{ $item->name }}"
                                aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                                <img src="{{ asset('storage/'.$image) }}" alt="{{ $item->name }} - imagem {{ $loop->iteration }}" class="h-16 w-full rounded object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-slate-950">{{ $item->name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ number_format((float) $item->stock_quantity, 2, ',', '.') }} disponível</p>
                    </div>
                    <div class="text-right text-lg font-bold text-slate-950">R$ {{ number_format((float) $item->sale_price, 2, ',', '.') }}</div>
                </div>
                @if ($canUseCustomerActions)
                    <form method="POST" action="{{ route('public.checkout.cart.add', $tenant->slug) }}" class="mt-5 flex gap-2">
                        @csrf
                        <input type="hidden" name="item_id" value="{{ $item->id }}">
                        <input name="quantity" type="number" min="1" step="1" value="1"
                            class="w-24 rounded-lg border-slate-300 text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        <button class="flex-1 rounded-lg border border-slate-900/10 px-4 py-2 text-sm font-extrabold text-slate-950 shadow-[0_3px_0_#ea580c]" style="background: {{ $primaryColor }}">
                            Adicionar
                        </button>
                    </form>
                @endif
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 sm:col-span-2 lg:col-span-3">
                Nenhum produto disponível no momento.
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $items->links() }}
    </div>

    @if ($canShowAdvancedCustomization && $customization?->google_maps_embed_src)
        <section class="mt-8 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-semibold text-slate-950">Localização</h2>
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

@push('scripts')
    <script>
        document.querySelectorAll('[data-product-thumbnail]').forEach((thumbnail) => {
            thumbnail.addEventListener('click', () => {
                const productId = thumbnail.dataset.productThumbnail;
                const mainImage = document.querySelector(`[data-product-main-image="${productId}"]`);

                if (!mainImage) {
                    return;
                }

                mainImage.src = thumbnail.dataset.imageSrc;
                mainImage.alt = thumbnail.dataset.imageAlt;

                document.querySelectorAll(`[data-product-thumbnail="${productId}"]`).forEach((button) => {
                    const isSelected = button === thumbnail;

                    button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                    button.classList.toggle('border-orange-500', isSelected);
                    button.classList.toggle('ring-2', isSelected);
                    button.classList.toggle('ring-orange-200', isSelected);
                    button.classList.toggle('border-slate-100', ! isSelected);
                });
            });
        });
    </script>
@endpush
