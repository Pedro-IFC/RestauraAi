<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeatureMiddleware
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = Auth::user()?->tenant;

        if (! $tenant || ! $tenant->hasFeature($feature)) {
            if ($request->expectsJson()) {
                abort(403, 'Recurso não disponível no plano atual.');
            }

            return redirect()
                ->route('tenant.plan.blocked')
                ->with('error', 'Recurso não disponível no plano atual.');
        }

        return $next($request);
    }
}
