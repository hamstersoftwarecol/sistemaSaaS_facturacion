<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a los módulos si la suscripción de la empresa no está vigente.
 */
class EnsureSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $subscription = $request->user()->company?->subscription;

        if (! $subscription || ! $subscription->isValid()) {
            return redirect()->route('subscription.index')
                ->with('warning', 'Su suscripción no está vigente. Elija un plan para seguir usando el sistema.');
        }

        return $next($request);
    }
}
