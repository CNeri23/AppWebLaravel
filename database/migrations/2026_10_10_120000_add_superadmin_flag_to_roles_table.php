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
            DB::table('roles')->insert([
                'name' => 'Superadministrador',
                'description' => 'Rol reservado para administrar los permisos de otros roles.',
                'is_superadmin' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        });
    }
};
