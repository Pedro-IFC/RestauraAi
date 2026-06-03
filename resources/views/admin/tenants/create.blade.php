@extends('layouts.admin')

@section('title', 'Nova Assistência')

@section('content')
    <div class="mx-auto max-w-3xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <a href="{{ route('admin.tenants.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Voltar</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-950">Nova assistência</h1>
            <p class="mt-1 text-sm text-gray-600">O período de trial será aplicado automaticamente com base no plano escolhido.</p>
        </div>

        <form method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-6">
            @csrf

            <section>
                <h2 class="font-semibold text-gray-900">Assinatura</h2>
                <div class="mt-4">
                    <label for="plan_id" class="block text-sm font-medium text-gray-700">Plano</label>
                    <select id="plan_id" name="plan_id" required class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Selecione</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                {{ $plan->name }} - {{ $plan->trial_days_allowed }} dia(s) grátis
                            </option>
                        @endforeach
                    </select>
                </div>
            </section>

            <section>
                <h2 class="font-semibold text-gray-900">Dados da assistência</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700">Slug público</label>
                        <input id="slug" name="slug" type="text" value="{{ old('slug') }}" placeholder="gerado pelo nome se vazio"
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label for="document" class="block text-sm font-medium text-gray-700">Documento</label>
                        <input id="document" name="document" type="text" value="{{ old('document') }}" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </section>

            <section>
                <h2 class="font-semibold text-gray-900">Administrador da assistência</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="owner_name" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input id="owner_name" name="owner_name" type="text" value="{{ old('owner_name') }}" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="owner_email" class="block text-sm font-medium text-gray-700">E-mail</label>
                        <input id="owner_email" name="owner_email" type="email" value="{{ old('owner_email') }}" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="md:col-span-2">
                        <label for="owner_password" class="block text-sm font-medium text-gray-700">Senha inicial</label>
                        <input id="owner_password" name="owner_password" type="password" required
                            class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-5">
                <a href="{{ route('admin.tenants.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Criar com trial
                </button>
            </div>
        </form>
    </div>
@endsection
