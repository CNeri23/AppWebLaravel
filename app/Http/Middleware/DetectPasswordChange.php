<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DetectPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $usuario = Auth::user();
        $hashActual = $usuario->getAuthPassword();
        $hashSesion = $request->session()->get('password_hash');

        // La primera petición autenticada establece la huella de contraseña de esta sesión.
        if (! is_string($hashSesion) || $hashSesion === '') {
            $request->session()->put('password_hash', $hashActual);

            return $next($request);
        }

        if (hash_equals($hashActual, $hashSesion)) {
            return $next($request);
        }

        AuditLogService::log(
            module: 'autenticacion',
            action: 'SESION_CERRADA_PASSWORD_CAMBIADA',
            description: 'Se cerró la sesión del usuario "' . $usuario->username .
                '" porque su contraseña cambió después de iniciar sesión.',
            entity: $usuario
        );

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Tu sesión se cerró porque tu contraseña fue cambiada. Contacta al Superadministrador o al Administrador para obtener acceso nuevamente.',
                'motivo' => 'password_changed',
            ], 401);
        }

        return redirect()->route('login', ['password_cambiada' => 1]);
    }
}