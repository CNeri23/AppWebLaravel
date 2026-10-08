<?php

namespace Database\Seeders;

use App\Models\Accion;
use App\Models\RolePermission;
use App\Models\Submodulo;
use Illuminate\Database\Seeder;

/**
 * Da de alta el submódulo "Asistencias" y sus acciones dentro del mismo módulo
 * donde está "Membresías", y lo asigna a los roles que ya trabajan con membresías.
 *
 * Es seguro ejecutarlo varias veces: no duplica ni pisa lo que ya editaste.
 *
 *   php artisan db:seed --class=AsistenciaSeeder
 */
class AsistenciaSeeder extends Seeder
{
    public function run(): void
    {
        $membresias = Submodulo::where('slug', 'membresias')->first();

        if (!$membresias) {
            $this->command?->warn('No existe el submódulo "membresias"; no se creó "asistencias".');

            return;
        }

        $submodulo = Submodulo::firstOrCreate(
            ['slug' => 'asistencias'],
            [
                'modulo_id' => $membresias->modulo_id,
                'nombre' => 'Asistencias',
                'descripcion' => 'Registro de entradas de miembros',
                'icono' => '<i class="fa-solid fa-clipboard-user"></i>',
                'ruta' => 'asistencias.index',
                'orden' => ((int) Submodulo::where('modulo_id', $membresias->modulo_id)->max('orden')) + 1,
                'activo' => true,
            ]
        );

        $registrar = Accion::firstOrCreate(
            ['slug' => 'asistencias.registrar'],
            [
                'submodulo_id' => $submodulo->id,
                'nombre' => 'Registrar entrada',
                'descripcion' => 'Registrar la entrada de un miembro',
                'icono' => '<i class="fa-solid fa-right-to-bracket"></i>',
                'orden' => 1,
                'activo' => true,
            ]
        );

        $eliminar = Accion::firstOrCreate(
            ['slug' => 'asistencias.eliminar'],
            [
                'submodulo_id' => $submodulo->id,
                'nombre' => 'Anular',
                'descripcion' => 'Anular un registro de asistencia',
                'icono' => '<i class="fa-regular fa-trash-can"></i>',
                'orden' => 2,
                'activo' => true,
            ]
        );

        // Roles que ya usan Membresías: pueden ver y registrar asistencias.
        $rolesMembresias = RolePermission::query()
            ->where('permission_type', 'submodulo')
            ->where('permission_id', $membresias->id)
            ->pluck('role_id');

        foreach ($rolesMembresias as $roleId) {
            $this->conceder($roleId, 'submodulo', $submodulo->id);
            $this->conceder($roleId, 'accion', $registrar->id);
        }

        // Anular solo para quienes ya pueden cancelar membresías.
        $cancelar = Accion::where('slug', 'membresias.cancelar')->first();

        if ($cancelar) {
            RolePermission::query()
                ->where('permission_type', 'accion')
                ->where('permission_id', $cancelar->id)
                ->pluck('role_id')
                ->each(fn ($roleId) => $this->conceder($roleId, 'accion', $eliminar->id));
        }
    }

    private function conceder(int $roleId, string $tipo, int $permisoId): void
    {
        RolePermission::firstOrCreate([
            'role_id' => $roleId,
            'permission_type' => $tipo,
            'permission_id' => $permisoId,
        ]);
    }
}