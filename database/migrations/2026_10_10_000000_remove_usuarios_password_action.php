<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('acciones')) {
            return;
        }

        $accion = DB::table('acciones')
            ->where('slug', 'usuarios.password')
            ->first();

        if (!$accion) {
            return;
        }

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')
                ->where('permission_type', 'accion')
                ->where('permission_id', $accion->id)
                ->delete();
        }

        DB::table('acciones')
            ->where('id', $accion->id)
            ->delete();
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('acciones')
            || !Schema::hasTable('submodulos')
            || DB::table('acciones')->where('slug', 'usuarios.password')->exists()
        ) {
            return;
        }

        $submoduloId = DB::table('submodulos')
            ->where('slug', 'usuarios')
            ->value('id');

        if (!$submoduloId) {
            return;
        }

        DB::table('acciones')->insert([
            'submodulo_id' => $submoduloId,
            'nombre' => 'Cambiar contraseña',
            'slug' => 'usuarios.password',
            'descripcion' => 'Permite cambiar la contraseña de un usuario desde Administración.',
            'icono' => 'fa-solid fa-key',
            'orden' => 0,
            'activo' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
