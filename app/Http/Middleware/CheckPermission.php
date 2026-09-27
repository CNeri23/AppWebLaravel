<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $tipo,
        string $identificador
    ): Response {

        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $permissionService = app(PermissionService::class);

        $tienePermiso = match ($tipo) {

            'modulo' => $permissionService->tieneModuloPorSlug(
                auth()->user(),
                $identificador
            ),

            'submodulo' => $permissionService->tieneSubmoduloPorSlug(
                auth()->user(),
                $identificador
            ),

            'accion' => $permissionService->tieneAccionPorSlug(
                auth()->user(),
                $identificador
            ),

            default => false,
        };

        if (!$tienePermiso) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}