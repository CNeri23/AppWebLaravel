<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use App\Services\SystemSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    private const SESSION_KEY = 'last_activity_at';

    public function __construct(private SystemSettings $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $minutos = (int) $this->settings->get('session_timeout', 0);

        if ($minutos <= 0) {
            return $next($request);
        }

        $ahora = time();
        $ultimaActividad = (int) $request->session()->get(self::SESSION_KEY, $ahora);

        if ($ahora - $ultimaActividad > $minutos * 60) {
            $usuario = Auth::user();

            AuditLogService::log(
                module: 'autenticacion',
                action: 'SESION_EXPIRADA',
                description: 'La sesión del usuario "' . $usuario->name .
                '" se cerró por inactividad (' . $minutos . ' min).',
                entity: $usuario
            );

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Tu sesión expiró por inactividad. Inicia sesión de nuevo.',
                ], 401);
            }

            return redirect()->route('login', ['expirada' => 1]);
        }

        $request->session()->put(self::SESSION_KEY, $ahora);

        return $next($request);
    }
}