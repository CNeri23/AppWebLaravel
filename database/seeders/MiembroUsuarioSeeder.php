<?php

namespace Database\Seeders;

use App\Models\Accion;
use App\Models\RolePermission;
use App\Models\Submodulo;
use Illuminate\Database\Seeder;

/**
 * Agrega la acción "Usuario de acceso" a Miembros (botón para crear o vincular
 * el usuario con el que una persona entra al sistema) y se la concede a los roles
 * que ya pueden crear usuarios.
 *
 * Es seguro ejecutarlo varias veces.
 *
 *   php artisan db:seed --class=MiembroUsuarioSeeder
 */
class MiembroUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $miembros = Submodulo::where('slug', 'miembros')->first();

        if (!$miembros) {
            $this->command?->warn('No existe el submódulo "miembros"; no se creó la acción.');

            return;
        }

        $accion = Accion::firstOrCreate(
            ['slug' => 'miembros.usuario'],
            [
                'submodulo_id' => $miembros->id,
                'nombre' => 'Usuario de acceso',
                'descripcion' => 'Crear o asignar el usuario con el que un miembro entra al sistema',
                'icono' => '<i class="fa-solid fa-user-lock"></i>',
                'orden' => ((int) Accion::where('submodulo_id', $miembros->id)->max('orden')) + 1,
                'activo' => true,
            ]
        );

        $crearUsuarios = Accion::where('slug', 'usuarios.crear')->first();

        if (!$crearUsuarios) {
            return;
        }

        RolePermission::query()
            ->where('permission_type', 'accion')
            ->where('permission_id', $crearUsuarios->id)
            ->pluck('role_id')
            ->each(function ($roleId) use ($accion) {
                RolePermission::firstOrCreate([
                    'role_id' => $roleId,
                    'permission_type' => 'accion',
                    'permission_id' => $accion->id,
                ]);
            });
    }
}
