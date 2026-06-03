<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IsSuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se o usuário é o dono do SaaS (Super Admin)
        if (Auth::check() && Auth::user()->role === 'superadmin') {
            return $next($request);
        }

        // Retorna erro 403 (Proibido) se um lojista tentar acessar a rota /admin
        abort(403, 'Acesso restrito apenas para administradores da plataforma.');
    }
}
