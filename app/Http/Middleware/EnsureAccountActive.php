<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Una cuenta suspendida pierde el acceso en su siguiente petición, aunque
 * conserve una sesión abierta: se cierra la sesión y se responde 403.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if ($user && $user->isSuspended()) {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'message' => 'Tu cuenta está suspendida. Escríbenos desde la página de contacto si crees que es un error.',
                'suspended' => true,
            ], 403);
        }

        return $next($request);
    }
}
