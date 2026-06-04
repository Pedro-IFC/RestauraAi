@extends('layouts.app')

@section('title', 'Customização do Microssite')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Customização do microssite</h1>
        <p class="text-sm text-gray-600">Gerencie a identidade visual e os dados exibidos na página pública da assistência.</p>
    </div>

    <form method="POST" action="{{ route('tenant.customization.update') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1fr_360px]">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-gray-900">Identidade visual</h2>
                @unless ($canUseAdvancedCustomization)
                    <p class="mt-1 text-sm text-amber-700">Logo, cores, banners, Instagram e Google Maps integrado exigem personalização avançada.</p>
                @endunless

                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-gray-700">Cor principal</label>
                        <div class="mt-1 flex gap-2">
                            <input id="primary_color" type="color" value="{{ old('primary_color', $customization->primary_color ?? '#facc15') }}"
                                class="h-10 w-14 rounded-lg border border-gray-300" data-color-picker="primary_color" @disabled(! $canUseAdvancedCustomization)>
                            <input id="primary_color_text" name="primary_color" type="text" value="{{ old('primary_color', $customization->primary_color ?? '#facc15') }}"
                                class="min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500" @readonly(! $canUseAdvancedCustomization)>
                        </div>
                    </div>

                    <div>
                        <label for="secondary_color" class="block text-sm font-medium text-gray-700">Cor secundária</label>
                        <div class="mt-1 flex gap-2">
                            <input id="secondary_color" type="color" value="{{ old('secondary_color', $customization->secondary_color ?? '#fffbeb') }}"
                                class="h-10 w-14 rounded-lg border border-gray-300" data-color-picker="secondary_color" @disabled(! $canUseAdvancedCustomization)>
                            <input id="secondary_color_text" name="secondary_color" type="text" value="{{ old('secondary_color', $customization->secondary_color ?? '#fffbeb') }}"
                                class="min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500" @readonly(! $canUseAdvancedCustomization)>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="logo" class="block text-sm font-medium text-gray-700">Logo</label>
                    <input id="logo" name="logo" type="file" accept="image/*"
                        class="mt-1 block w-full rounded-lg border border-gray-300 text-sm file:mr-4 file:border-0 file:bg-gray-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" @disabled(! $canUseAdvancedCustomization)>
                    @if ($customization->logo)
                        <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-orange-600" @disabled(! $canUseAdvancedCustomization)>
                            Remover logo atual
                        </label>
                    @endif
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-gray-900">Banners do microssite</h2>
                <p class="mt-1 text-sm text-gray-500">Envie até 5 imagens. Elas aparecem no topo da página pública.</p>

                <input name="banners[]" type="file" accept="image/*" multiple
                    class="mt-4 block w-full rounded-lg border border-gray-300 text-sm file:mr-4 file:border-0 file:bg-gray-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white" @disabled(! $canUseAdvancedCustomization)>

                @if (filled($customization->banners))
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @foreach ($customization->banners as $banner)
                            <label class="overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                <img src="{{ asset('storage/'.$banner) }}" alt="Banner atual" class="h-32 w-full object-cover">
                                <span class="flex items-center gap-2 p-3 text-sm text-gray-700">
                                    <input type="checkbox" name="remove_banners[]" value="{{ $banner }}" class="rounded border-gray-300 text-orange-600" @disabled(! $canUseAdvancedCustomization)>
                                    Remover este banner
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="font-semibold text-gray-900">Conteúdo e localização</h2>

                <div class="mt-5 space-y-5">
                    <div>
                        <label for="about_text" class="block text-sm font-medium text-gray-700">Descrição formatada</label>
                        <textarea id="about_text" name="about_text" rows="8"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                            placeholder="Conte sobre a assistência, especialidades, políticas de atendimento e garantias.">{{ old('about_text', $customization->about_text) }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Quebras de linha serão preservadas no microssite.</p>
                    </div>

                    <div>
                        <label for="instagram_handle" class="block text-sm font-medium text-gray-700">Instagram</label>
                        <input id="instagram_handle" name="instagram_handle" type="text" value="{{ old('instagram_handle', $customization->instagram_handle) }}"
                            placeholder="@suaassistencia ou URL do perfil"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500" @readonly(! $canUseAdvancedCustomization)>
                    </div>

                    <div>
                        <label for="address_text" class="block text-sm font-medium text-gray-700">Endereço</label>
                        <textarea id="address_text" name="address_text" rows="3"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">{{ old('address_text', $customization->address_text) }}</textarea>
                    </div>

                    <div>
                        <label for="google_maps_iframe" class="block text-sm font-medium text-gray-700">Google Maps</label>
                        <textarea id="google_maps_iframe" name="google_maps_iframe" rows="5"
                            class="mt-1 w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500"
                            placeholder="Cole o iframe de incorporação ou a URL do Google Maps" @readonly(! $canUseAdvancedCustomization)>{{ old('google_maps_iframe', $customization->google_maps_iframe) }}</textarea>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Prévia rápida</h2>
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
                    <div class="h-2 bg-[repeating-linear-gradient(135deg,#111827_0_10px,#ffffff_10px_20px)]"></div>
                    <div class="p-4" style="background: {{ old('secondary_color', $customization->secondary_color ?? '#fffbeb') }}">
                        @if ($customization->logo)
                            <img src="{{ asset('storage/'.$customization->logo) }}" alt="Logo atual" class="mb-3 max-h-16 object-contain">
                        @endif
                        <div class="text-xl font-bold text-gray-950">
                            {{ auth()->user()->tenant?->name ?? 'Sua assistência' }}
                        </div>
                        <p class="mt-2 text-sm text-gray-700">{{ str($customization->about_text)->limit(120) }}</p>
                    </div>
                </div>
            </section>

            <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-gray-900">Publicação</h2>
                <p class="mt-2 text-sm text-gray-600">As alterações são aplicadas imediatamente no microssite público.</p>
                <button class="mt-5 w-full rounded-lg bg-yellow-300 px-4 py-2 text-sm font-extrabold text-gray-950 shadow-[0_4px_0_#f97316] hover:bg-yellow-400">
                    Salvar customização
                </button>
                @if (auth()->user()->tenant?->slug)
                    <a href="/{{ auth()->user()->tenant->slug }}" class="mt-3 block text-center text-sm font-semibold text-orange-600 hover:text-orange-700">
                        Ver microssite
                    </a>
                @endif
            </section>
        </aside>
    </form>
@endsection

@push('scripts')
    <script>
        (() => {
            document.querySelectorAll('[data-color-picker]').forEach((picker) => {
                const textInput = document.getElementById(`${picker.dataset.colorPicker}_text`);

                if (!textInput) {
                    return;
                }

                picker.addEventListener('input', () => {
                    textInput.value = picker.value;
                });

                textInput.addEventListener('input', () => {
                    if (/^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
                        picker.value = textInput.value;
                    }
                });
            });
        })();
    </script>
@endpush
