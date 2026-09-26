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

        return redirect()->route('roles.index')
            ->with('success', 'Rol creado correctamente.');
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

        return redirect()->route('roles.index')
            ->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Role $rol)
    {
        $nombre = $rol->name;
        $descripcion = $rol->description;

        AuditLogService::log(
            module: 'roles',
            action: 'ELIMINAR_ROL',
            description: 'Se eliminó el rol "' . $nombre .
                '" con descripción "' .
                ($descripcion ?? 'Sin descripción') . '".',
            entity: $rol
        );

        $rol->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Rol eliminado correctamente.');
    }
}