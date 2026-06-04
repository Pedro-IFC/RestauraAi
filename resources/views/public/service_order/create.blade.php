@extends('layouts.app')

@section('title', 'Abrir chamado')

@section('content')
    @php
        $customerName = old('name', $customer?->name ?? auth()->user()?->name);
        $customerCpf = old('cpf', $customer?->cpf);
        $customerPhone = old('phone', $customer?->phone);
        $customerEmail = old('email', $customer?->email ?? auth()->user()?->email);
    @endphp

    <div class="mx-auto max-w-3xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-wide text-blue-600">{{ $tenant->name }}</p>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">Abrir chamado</h1>
        <p class="mt-1 text-sm text-gray-600">Informe os dados do aparelho para a assistência iniciar a triagem.</p>

        @if (! auth()->user()?->isCustomer())
            <div class="mt-5 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p>Entre com código por e-mail para preencher seus dados e vincular este chamado ao histórico.</p>
                    <a href="{{ route('public.customer.login', ['slug' => $tenant->slug, 'intended' => '/'.$tenant->slug.'/chamados/novo']) }}"
                        class="w-fit rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                        Login rápido
                    </a>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('public.os.store', $tenant->slug) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                    <input id="name" name="name" type="text" value="{{ $customerName }}" required
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                    <input id="cpf" name="cpf" type="text" value="{{ $customerCpf }}"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input id="phone" name="phone" type="text" value="{{ $customerPhone }}"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ $customerEmail }}"
                        class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label for="device_model" class="block text-sm font-medium text-gray-700">Aparelho / modelo</label>
                <input id="device_model" name="device_model" type="text" value="{{ old('device_model') }}" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="defect_symptoms" class="block text-sm font-medium text-gray-700">Sintomas relatados</label>
                <textarea id="defect_symptoms" name="defect_symptoms" rows="5" required
                    class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('defect_symptoms') }}</textarea>
            </div>

            <div>
                <label for="attachments" class="block text-sm font-medium text-gray-700">Fotos ou vídeos do defeito</label>
                <input id="attachments" name="attachments[]" type="file" multiple accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/x-msvideo"
                    class="mt-1 block w-full rounded-lg border border-gray-300 text-sm text-gray-700 file:mr-4 file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                <p class="mt-1 text-xs text-gray-500">Até 6 arquivos. Imagens ou vídeos de até 20 MB cada.</p>
            </div>

            <div class="flex justify-end">
                <button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Enviar chamado
                </button>
            </div>
        </form>
    </div>
@endsection
