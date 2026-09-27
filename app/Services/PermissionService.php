<?php

namespace App\Services;

use App\Models\Accion;
use App\Models\Modulo;
use App\Models\RolePermission;
use App\Models\Submodulo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PermissionService
{
    public function tieneModulo(User $usuario, int $moduloId): bool
    {
        return $this->tienePermiso(
            $usuario,
            'modulo',
            $moduloId
        );
    }

    public function tieneSubmodulo(User $usuario, int $submoduloId): bool
    {
        return $this->tienePermiso(
            $usuario,
            'submodulo',
            $submoduloId
        );
    }

    public function tieneAccion(User $usuario, int $accionId): bool
    {
        return $this->tienePermiso(
            $usuario,
            'accion',
            $accionId
        );
    }

    public function tieneModuloPorSlug(User $usuario, string $slug): bool
    {
        return $this->tienePermisoPorSlug(
            $usuario,
            'modulo',
            $slug
        );
    }

    public function tieneSubmoduloPorSlug(User $usuario, string $slug): bool
    {
        return $this->tienePermisoPorSlug(
            $usuario,
            'submodulo',
            $slug
        );
    }

    public function tieneAccionPorSlug(User $usuario, string $slug): bool
    {
        return $this->tienePermisoPorSlug(
            $usuario,
            'accion',
            $slug
        );
    }

    public function tienePermisoPorSlug(
        User $usuario,
        string $tipo,
        string $slug
    ): bool {
        $modelo = $this->buscarModeloPorSlug($tipo, $slug);

        if (!$modelo) {
            return false;
        }

        return $this->tienePermiso(
            $usuario,
            $tipo,
            $modelo->id
        );
    }

    public function tienePermiso(
        User $usuario,
        string $tipo,
        int $id
    ): bool {
        return RolePermission::query()
            ->whereIn(
                'role_id',
                $usuario->roles()->pluck('roles.id')
            )
            ->where('permission_type', $tipo)
            ->where('permission_id', $id)
            ->exists();
    }

    public function permisos(User $usuario): Collection
    {
        return $usuario->permissions();
    }

    protected function buscarModeloPorSlug(
        string $tipo,
        string $slug
    ): ?Model {
        return match ($tipo) {

            'modulo' => Modulo::query()
                ->where('slug', $slug)
                ->where('activo', true)
                ->first(),

            'submodulo' => Submodulo::query()
                ->where('slug', $slug)
                ->where('activo', true)
                ->first(),

            'accion' => Accion::query()
                ->where('slug', $slug)
                ->where('activo', true)
                ->first(),

            default => null,
        };
    }
}