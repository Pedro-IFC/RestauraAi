@extends('layouts.admin')

@section('title', 'Consumidores')

@section('content')
    <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Consumidores</h1>
            <p class="mt-1 text-sm text-gray-600">Gestão LGPD de contas comuns da plataforma.</p>
        </div>
    </div>

    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.consumers.index') }}" class="grid gap-3 md:grid-cols-[1fr_auto]">
            <input name="search" value="{{ request('search') }}" placeholder="Buscar por nome, e-mail, CPF ou telefone"
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <button class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Buscar
            </button>
        </form>

        <div class="mt-5 overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Consumidor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Vínculos</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Criado em</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($consumers as $consumer)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ $consumer->name }}</div>
                                <div class="text-sm text-gray-600">{{ $consumer->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $consumer->customers_count }} assistência(s)
                                <span class="text-gray-400">-</span>
                                {{ $consumer->customer_addresses_count }} endereço(s)
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $consumer->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.consumers.export', $consumer) }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                        Exportar
                                    </a>
                                    <a href="{{ route('admin.consumers.show', $consumer) }}" class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">
                                        Abrir
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">
                                Nenhum consumidor encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $consumers->links() }}
        </div>
    </section>
@endsection
