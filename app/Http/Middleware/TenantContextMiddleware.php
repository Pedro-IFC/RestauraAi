<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantContextMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário está logado e se possui um tenant_id vinculado
        if (Auth::check() && Auth::user()->tenant_id) {

            // Dica para o futuro: Aqui você poderá adicionar a lógica do RF-11.3
            // para verificar se o status da assinatura do Tenant está 'suspended'
            // e redirecionar para uma tela de cobrança, se necessário.

            return $next($request);
        }

        // Se não tiver tenant_id, desloga e manda pro login
        Auth::logout();
        return redirect()->route('login')->with('error', 'Acesso negado. Sua conta não está vinculada a nenhuma assistência.');
    }
}
