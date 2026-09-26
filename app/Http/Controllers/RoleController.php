<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $rol = Role::create([
            'name' => $datos['name'],
            'description' => $datos['description'] ?? null,
        ]);

        AuditLogService::log(
            module: 'roles',
            action: 'CREAR_ROL',
            description: 'Se creó el rol "' . $rol->name .
                '". Descripción: "' . ($rol->description ?? 'Sin descripción') . '".',
            entity: $rol
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Rol creado correctamente.',
            'rol' => $rol,
            'fecha_registro' => $rol->created_at?->format('d/m/Y H:i'),
            'urls' => [
                'update' => route('roles.update', $rol),
                'delete' => route('roles.destroy', $rol),
            ],
        ]);
    }

    public function update(Request $request, Role $rol)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name,' . $rol->id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $nombreAnterior = $rol->name;
        $descripcionAnterior = $rol->description;

        $rol->name = $datos['name'];
        $rol->description = $datos['description'] ?? null;

        $cambios = [];

        if ($nombreAnterior !== $rol->name) {
            $cambios[] = 'Nombre: "' . $nombreAnterior .
                '" → "' . $rol->name . '"';
        }

        if ($descripcionAnterior !== $rol->description) {

            $descripcionAnteriorTexto = $descripcionAnterior ?? 'Sin descripción';
            $descripcionNuevaTexto = $rol->description ?? 'Sin descripción';

            $cambios[] = 'Descripción: "' . $descripcionAnteriorTexto .
                '" → "' . $descripcionNuevaTexto . '"';
        }

        $rol->save();

        if (!empty($cambios)) {

            AuditLogService::log(
                module: 'roles',
                action: 'EDITAR_ROL',
                description: 'Se modificó el rol "' . $rol->name .
                    '". Cambios: ' . implode(' | ', $cambios),
                entity: $rol
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Rol actualizado correctamente.',
            'rol' => $rol,
            'fecha_registro' => $rol->created_at?->format('d/m/Y H:i'),
            'urls' => [
                'update' => route('roles.update', $rol),
                'delete' => route('roles.destroy', $rol),
            ],
        ]);
    }

    public function destroy(Role $rol)
    {
        $nombre = $rol->name;
        $descripcion = $rol->description;
        $id = $rol->id;

        AuditLogService::log(
            module: 'roles',
            action: 'ELIMINAR_ROL',
            description: 'Se eliminó el rol "' . $nombre .
                '" con descripción "' .
                ($descripcion ?? 'Sin descripción') . '".',
            entity: $rol
        );

        $rol->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'Rol eliminado correctamente.',
            'id' => $id,
        ]);
    }
}
