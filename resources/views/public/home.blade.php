@extends('layouts.app')

@section('title', 'A Plataforma Definitiva para Assistências')

@section('content')
    <div class="relative mb-16 mt-4 overflow-hidden rounded-2xl border border-yellow-300 bg-gray-950 p-10 text-white shadow-xl sm:p-16">
        <div class="absolute inset-x-0 top-0 h-4 bg-[repeating-linear-gradient(135deg,#111827_0_12px,#ffffff_12px_24px)]"></div>
        <div class="absolute -right-16 -top-16 h-52 w-52 rounded-full bg-orange-500/30 blur-3xl"></div>
        <div class="absolute -bottom-24 left-12 h-56 w-56 rounded-full bg-yellow-400/25 blur-3xl"></div>

        <div class="relative mx-auto max-w-4xl text-center">
            <div class="mb-6 inline-flex items-center rounded-full border border-yellow-300/50 bg-yellow-300 px-4 py-2 text-sm font-extrabold uppercase tracking-wide text-gray-950 shadow-[0_4px_0_#f97316]">
                Sistema para bancada, loja e atendimento
            </div>
            <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">
                Evolua a gestão da sua <span class="text-yellow-300">Assistência Técnica</span>
            </h1>
            <p class="mx-auto mb-8 mt-6 max-w-3xl text-lg font-light text-gray-200 sm:text-xl">
                Abandone o papel. O RestauraAí integra o gerenciamento da sua bancada com um catálogo online exclusivo para seus clientes aprovarem orçamentos e comprarem seus produtos.
            </p>
            <div class="flex flex-col justify-center gap-4 sm:flex-row">
                <a href="#" class="rounded-full bg-yellow-300 px-8 py-3 font-extrabold text-gray-950 shadow-[0_5px_0_#f97316] transition hover:bg-yellow-400">
                    Começar Teste Grátis
                </a>
                <a href="#recursos" class="rounded-full border border-white/25 bg-white/10 px-8 py-3 font-semibold text-white shadow-md transition hover:bg-white hover:text-gray-950">
                    Ver Recursos
                </a>
            </div>
        </div>

        <div class="relative mx-auto mt-10 grid max-w-3xl gap-3 text-left sm:grid-cols-3">
            <div class="rounded-lg border border-white/10 bg-white/10 p-4">
                <div class="text-2xl font-black text-yellow-300">OS</div>
                <div class="mt-1 text-sm text-gray-300">Fluxo da bancada sem papel.</div>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 p-4">
                <div class="text-2xl font-black text-yellow-300">Loja</div>
                <div class="mt-1 text-sm text-gray-300">Produtos e orçamentos aprovados.</div>
            </div>
            <div class="rounded-lg border border-white/10 bg-white/10 p-4">
                <div class="text-2xl font-black text-yellow-300">Cliente</div>
                <div class="mt-1 text-sm text-gray-300">Acompanhamento e aprovação online.</div>
            </div>
        </div>
    </div>

    <div id="recursos" class="mb-16">
        <div class="mb-12 text-center">
            <h2 class="text-3xl font-bold text-gray-900">Tudo que sua loja precisa em um só lugar</h2>
            <p class="mt-4 text-gray-600">Projetado para facilitar a vida do técnico e impressionar o cliente final.</p>
            <div class="mx-auto mt-5 h-2 w-32 rounded-full bg-[repeating-linear-gradient(135deg,#111827_0_10px,#ffffff_10px_20px)] ring-1 ring-gray-200"></div>
        </div>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">

            <div class="rounded-xl border border-yellow-200 bg-white p-6 shadow-md transition hover:border-orange-400">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-300 text-gray-950 shadow-[0_3px_0_#f97316]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                </div>
                <h3 class="mb-2 text-xl font-semibold text-gray-900">Kanban Operacional</h3>
                <p class="text-sm text-gray-600">Organize suas Ordens de Serviço (OS) visualmente. Mova aparelhos entre colunas como "Triagem", "Na Bancada" e "Pronto".</p>
            </div>

            <div class="rounded-xl border border-yellow-200 bg-white p-6 shadow-md transition hover:border-orange-400">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-300 text-gray-950 shadow-[0_3px_0_#f97316]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="mb-2 text-xl font-semibold text-gray-900">Catálogo & Loja Online</h3>
                <p class="text-sm text-gray-600">Tenha uma página com a sua marca (ex: <i>restauraai.com.br/sua-loja</i>) para vender acessórios e peças diretamente do seu estoque.</p>
            </div>

            <div class="rounded-xl border border-yellow-200 bg-white p-6 shadow-md transition hover:border-orange-400">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-300 text-gray-950 shadow-[0_3px_0_#f97316]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <h3 class="mb-2 text-xl font-semibold text-gray-900">Portal do Cliente</h3>
                <p class="text-sm text-gray-600">Seus clientes podem consultar o status do conserto usando o CPF, aprovar orçamentos online e evitar ligações desnecessárias.</p>
            </div>

            <div class="rounded-xl border border-yellow-200 bg-white p-6 shadow-md transition hover:border-orange-400">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-300 text-gray-950 shadow-[0_3px_0_#f97316]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <h3 class="mb-2 text-xl font-semibold text-gray-900">Gestão de Estoque</h3>
                <p class="text-sm text-gray-600">Controle insumos técnicos e produtos de venda. Ao aplicar uma peça na OS, o estoque é atualizado e o lucro calculado automaticamente.</p>
            </div>

            <div class="rounded-xl border border-yellow-200 bg-white p-6 shadow-md transition hover:border-orange-400">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-300 text-gray-950 shadow-[0_3px_0_#f97316]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="mb-2 text-xl font-semibold text-gray-900">Time Tracking</h3>
                <p class="text-sm text-gray-600">Monitore o tempo gasto pelos técnicos em cada aparelho. Entenda gargalos na produtividade e melhore seus prazos de entrega.</p>
            </div>

            <div class="rounded-xl border border-yellow-200 bg-white p-6 shadow-md transition hover:border-orange-400">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-300 text-gray-950 shadow-[0_3px_0_#f97316]">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 5 5 L22 4"></path></svg>
                </div>
                <h3 class="mb-2 text-xl font-semibold text-gray-900">Métricas e Dashboard</h3>
                <p class="text-sm text-gray-600">Acompanhe seu faturamento, lucro líquido em OS, alertas de estoque baixo e muito mais em um painel gerencial claro e objetivo.</p>
            </div>
        </div>
    </div>

    <div class="mb-8 overflow-hidden rounded-2xl bg-gray-950 text-center text-white shadow-xl">
        <div class="h-4 bg-[repeating-linear-gradient(135deg,#111827_0_12px,#ffffff_12px_24px)]"></div>
        <div class="p-10">
            <h2 class="mb-4 text-3xl font-bold">Pronto para digitalizar sua bancada?</h2>
            <p class="mx-auto mb-8 max-w-2xl text-gray-300">Junte-se às assistências técnicas que estão economizando tempo e aumentando suas vendas com o RestauraAí.</p>
            <a href="#" class="inline-block rounded-lg bg-yellow-300 px-8 py-3 font-extrabold text-gray-950 shadow-[0_5px_0_#f97316] transition hover:bg-yellow-400">
                Criar Minha Conta Agora
            </a>
        </div>
    </div>
@endsection
