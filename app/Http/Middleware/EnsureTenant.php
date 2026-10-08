<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza que el usuario pertenece a una empresa activa.
 */
class EnsureTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user->company_id) {
            return $user->is_super_admin
                ? redirect()->route('admin.dashboard')
                : abort(403, 'Su usuario no está asociado a ninguna empresa.');
        }

        if (! $user->is_active || $user->company?->status !== 'active') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => ! $user->is_active
                    ? 'Su usuario está desactivado. Contacte con el administrador de su empresa.'
                    : 'La cuenta de su empresa está suspendida. Contacte con soporte.',
            ]);
        }

        return $next($request);
    }
}
