<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $usuarios = User::with('roles')
            ->orderBy('name')
            ->get();

        $roles = Role::orderBy('name')->get();

        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
        ]);

        AuditLogService::log(
            module: 'usuarios',
            action: 'CREAR_USUARIO',
            description: 'Se creó el usuario "' . $usuario->name .
                '" con correo "' . $usuario->email . '".',
            entity: $usuario
        );

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $usuario)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $usuario->id],
        ]);

        $nombreAnterior = $usuario->name;
        $correoAnterior = $usuario->email;

        $usuario->name = $datos['name'];
        $usuario->email = $datos['email'];

        $cambios = [];

        if ($nombreAnterior !== $usuario->name) {
            $cambios[] = 'Nombre: "' . $nombreAnterior . '" → "' . $usuario->name . '"';
        }

        if ($correoAnterior !== $usuario->email) {
            $cambios[] = 'Correo: "' . $correoAnterior . '" → "' . $usuario->email . '"';
        }

        $usuario->save();

        if (!empty($cambios)) {

            AuditLogService::log(
                module: 'usuarios',
                action: 'EDITAR_USUARIO',
                description: 'Se modificó el usuario "' . $usuario->name .
                    '". Cambios: ' . implode(' | ', $cambios),
                entity: $usuario
            );
        }

        return redirect()->route('usuarios.index')
            ->with('success', 'Datos del usuario actualizados correctamente.');
    }

    public function updatePassword(Request $request, User $usuario)
    {
        $datos = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $usuario->password = Hash::make($datos['password']);

        $usuario->save();

        AuditLogService::log(
            module: 'usuarios',
            action: 'CAMBIAR_PASSWORD',
            description: 'Se cambió la contraseña del usuario "' .
                $usuario->name . '".',
            entity: $usuario
        );

        return redirect()->route('usuarios.index')
            ->with('success', 'Contraseña actualizada correctamente.');
    }

    public function updateRoles(Request $request, User $usuario)
    {
        $datos = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        $rolesAnteriores = $usuario->roles()
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $rolesNuevos = Role::whereIn('id', $datos['roles'] ?? [])
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $usuario->roles()->sync($datos['roles'] ?? []);

        $rolesAnterioresTexto = !empty($rolesAnteriores)
            ? implode(', ', $rolesAnteriores)
            : 'Sin roles';

        $rolesNuevosTexto = !empty($rolesNuevos)
            ? implode(', ', $rolesNuevos)
            : 'Sin roles';

        AuditLogService::log(
            module: 'usuarios',
            action: 'ASIGNAR_ROLES',
            description: 'Se actualizaron los roles del usuario "' .
                $usuario->name . '". Roles anteriores: "' .
                $rolesAnterioresTexto . '". Roles nuevos: "' .
                $rolesNuevosTexto . '".',
            entity: $usuario
        );

        return redirect()->route('usuarios.index')
            ->with('success', 'Roles del usuario actualizados correctamente.');
    }

    public function destroy(User $usuario)
    {
        $nombre = $usuario->name;
        $correo = $usuario->email;

        AuditLogService::log(
            module: 'usuarios',
            action: 'ELIMINAR_USUARIO',
            description: 'Se eliminó el usuario "' . $nombre .
                '" con correo "' . $correo . '".',
            entity: $usuario
        );

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}