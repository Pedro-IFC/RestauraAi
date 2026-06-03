@extends('layouts.app')

@section('title', 'A Plataforma Definitiva para Assistências')

@section('content')
    <div class="bg-blue-600 text-white rounded-2xl p-10 sm:p-16 text-center shadow-xl mb-16 mt-4">
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-6">
            Evolua a gestão da sua <span class="text-yellow-300">Assistência Técnica</span>
        </h1>
        <p class="text-lg sm:text-xl mb-8 max-w-3xl mx-auto font-light text-blue-100">
            Abandone o papel. O RestauraAí integra o gerenciamento da sua bancada com um catálogo online exclusivo para seus clientes aprovarem orçamentos e comprarem seus produtos.
        </p>
        <div class="flex justify-center gap-4">
            <a href="#" class="bg-white text-blue-700 font-bold py-3 px-8 rounded-full shadow-md hover:bg-gray-50 transition transform hover:-translate-y-1">
                Começar Teste Grátis
            </a>
            <a href="#recursos" class="bg-blue-800 text-white font-semibold py-3 px-8 rounded-full shadow-md hover:bg-blue-900 transition">
                Ver Recursos
            </a>
        </div>
    </div>

    <div id="recursos" class="mb-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900">Tudo que sua loja precisa em um só lugar</h2>
            <p class="mt-4 text-gray-600">Projetado para facilitar a vida do técnico e impressionar o cliente final.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-500 transition">
                <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Kanban Operacional</h3>
                <p class="text-gray-600 text-sm">Organize suas Ordens de Serviço (OS) visualmente. Mova aparelhos entre colunas como "Triagem", "Na Bancada" e "Pronto".</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-500 transition">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Catálogo & Loja Online</h3>
                <p class="text-gray-600 text-sm">Tenha uma página com a sua marca (ex: <i>restauraai.com.br/sua-loja</i>) para vender acessórios e peças diretamente do seu estoque.</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-500 transition">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Portal do Cliente</h3>
                <p class="text-gray-600 text-sm">Seus clientes podem consultar o status do conserto usando o CPF, aprovar orçamentos online e evitar ligações desnecessárias.</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-500 transition">
                <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Gestão de Estoque</h3>
                <p class="text-gray-600 text-sm">Controle insumos técnicos e produtos de venda. Ao aplicar uma peça na OS, o estoque é atualizado e o lucro calculado automaticamente.</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-500 transition">
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Time Tracking</h3>
                <p class="text-gray-600 text-sm">Monitore o tempo gasto pelos técnicos em cada aparelho. Entenda gargalos na produtividade e melhore seus prazos de entrega.</p>
            </div>

            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 hover:border-blue-500 transition">
                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-lg flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 5 5 L22 4"></path></svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Métricas e Dashboard</h3>
                <p class="text-gray-600 text-sm">Acompanhe seu faturamento, lucro líquido em OS, alertas de estoque baixo e muito mais em um painel gerencial claro e objetivo.</p>
            </div>
        </div>
    </div>

    <div class="bg-gray-900 text-white rounded-2xl p-10 text-center mb-8 shadow-xl">
        <h2 class="text-3xl font-bold mb-4">Pronto para digitalizar sua bancada?</h2>
        <p class="text-gray-400 mb-8 max-w-2xl mx-auto">Junte-se às assistências técnicas que estão economizando tempo e aumentando suas vendas com o RestauraAí.</p>
        <a href="#" class="inline-block bg-blue-600 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:bg-blue-500 transition">
            Criar Minha Conta Agora
        </a>
    </div>
@endsection
