<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('description');
        });

        $rol = DB::table('roles')
            ->where('name', 'Superadministrador')
            ->first();

        if ($rol) {
            DB::table('roles')
                ->where('id', $rol->id)
                ->update(['is_superadmin' => true]);
        } else {
            $id = DB::table('roles')->insertGetId([
                'name' => 'Superadministrador',
                'description' => 'Rol reservado para administrar los permisos de otros roles.',
                'is_superadmin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rol = (object) ['id' => $id];
        }

        // Copia los permisos de Administrador para que el nuevo rol sea funcional.
        $administrador = DB::table('roles')
            ->where('name', 'Administrador')
            ->first();

        if ($administrador) {
            DB::table('role_permissions')
                ->where('role_id', $rol->id)
                ->delete();

            $permisos = DB::table('role_permissions')
                ->where('role_id', $administrador->id)
                ->get(['permission_type', 'permission_id']);

            foreach ($permisos as $permiso) {
                DB::table('role_permissions')->insert([
                    'role_id' => $rol->id,
                    'permission_type' => $permiso->permission_type,
                    'permission_id' => $permiso->permission_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        });
    }
};
