<?php

namespace App\Http\Controllers;

use App\Models\Accion;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PasswordPolicy;
use App\Services\PermissionService;
use App\Services\UsuarioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $usuarios = User::with(['roles', 'persona'])
            ->get()
            ->sortBy(fn (User $usuario) => Str::lower($usuario->name))
            ->values();

        $roles = Role::orderBy('name')->get();

        $submoduloUsuarios = \App\Models\Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'usuarios')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesUsuarios = collect();

        if ($submoduloUsuarios) {
            $accionesUsuarios = $submoduloUsuarios->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        $politica = app(PasswordPolicy::class);

        $politicaPassword = [
            'min' => $politica->minLength(),
            'complex' => $politica->requiresComplexity(),
            'descripcion' => $politica->description(),
        ];

        return view('usuarios.index', compact(
            'usuarios',
            'roles',
            'accionesUsuarios',
            'politicaPassword'
        ));
    }

    public function store(
        Request $request,
        PasswordPolicy $politica,
        UsuarioService $servicio
    ) {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $datos = $request->validate([
            'username' => $servicio->reglasUsername(),
            ...$servicio->reglasPersona(),
            'password' => $politica->rules(),
            'activo' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ], [
            ...$servicio->mensajesUsername(),
            ...$servicio->mensajesPersona(),
            ...$politica->messages(),
        ]);

        // Primero se inserta el usuario y luego la persona con su usuario_id.
        $usuario = $servicio->crear(
            [
                'username' => $datos['username'],
                'password' => $datos['password'],
                'activo' => $request->boolean('activo', true),
            ],
            $datos,
            $datos['roles'] ?? []
        );

        AuditLogService::log(
            module: 'usuarios',
            action: 'CREAR_USUARIO',
            description: 'Se creó el usuario "' . $usuario->username .
                '" para "' . $usuario->name . '" (' . $usuario->email . ').',
            entity: $usuario
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Usuario creado correctamente.',
            'usuario' => $servicio->presentar($usuario),
            'urls' => $this->urls($usuario),
        ]);
    }

    public function update(
        Request $request,
        User $usuario,
        UsuarioService $servicio
    ) {
        $usuario->loadMissing('persona');

        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $datos = $request->validate([
            'username' => $servicio->reglasUsername($usuario->id),
            ...$servicio->reglasPersona($usuario->persona?->id),
        ], [
            ...$servicio->mensajesUsername(),
            ...$servicio->mensajesPersona(),
        ]);

        $cambios = [];

        DB::transaction(function () use ($usuario, $datos, &$cambios) {
            if ($usuario->username !== $datos['username']) {
                $cambios[] = 'Usuario: "' . $usuario->username . '" → "' . $datos['username'] . '"';
            }

            $usuario->username = $datos['username'];
            $usuario->save();

            $persona = $usuario->persona ?? new \App\Models\Persona(['usuario_id' => $usuario->id]);

            foreach (UsuarioService::CAMPOS_PERSONA as $campo) {
                $nuevo = $datos[$campo] ?? null;

                if ($persona->exists && ($persona->{$campo} ?? null) !== $nuevo) {
                    $cambios[] = ucfirst(str_replace('_', ' ', $campo)) .
                        ': "' . ($persona->{$campo} ?? '') . '" → "' . ($nuevo ?? '') . '"';
                }

                $persona->{$campo} = $nuevo;
            }

            $persona->usuario_id = $usuario->id;
            $persona->save();
        });

        $usuario->load(['persona', 'roles']);

        if (!empty($cambios)) {
            AuditLogService::log(
                module: 'usuarios',
                action: 'EDITAR_USUARIO',
                description: 'Se modificó el usuario "' . $usuario->username .
                    '". Cambios: ' . implode(' | ', $cambios),
                entity: $usuario
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Datos del usuario actualizados correctamente.',
            'usuario' => $servicio->presentar($usuario),
            'urls' => $this->urls($usuario),
        ]);
    }

    /**
     * Activa o desactiva el acceso al sistema de un usuario.
     */
    public function toggleEstado(Request $request, User $usuario, UsuarioService $servicio)
    {
        $datos = $request->validate([
            'activo' => ['required', 'boolean'],
        ]);

        $activo = (bool) $datos['activo'];

        if (!$activo && $usuario->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No puedes desactivar tu propia cuenta.',
            ], 422);
        }

        if ($usuario->activo !== $activo) {
            $usuario->activo = $activo;
            $usuario->save();

            AuditLogService::log(
                module: 'usuarios',
                action: $activo ? 'ACTIVAR_USUARIO' : 'DESACTIVAR_USUARIO',
                description: 'Se ' . ($activo ? 'activó' : 'desactivó') .
                    ' el usuario "' . $usuario->username . '".',
                entity: $usuario
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => $activo
                ? 'Usuario activado correctamente.'
                : 'Usuario desactivado correctamente. Ya no podrá iniciar sesión.',
            'usuario' => $servicio->presentar($usuario),
            'urls' => $this->urls($usuario),
        ]);
    }

    public function updatePassword(
        Request $request,
        User $usuario,
        PasswordPolicy $politica
    )
    {
        $datos = $request->validate([
            'password' => $politica->rules(),
        ], $politica->messages());

        $usuario->password = Hash::make($datos['password']);

        $usuario->save();

        AuditLogService::log(
            module: 'usuarios',
            action: 'CAMBIAR_PASSWORD',
            description: 'Se cambió la contraseña del usuario "' .
                $usuario->username . '".',
            entity: $usuario
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Contraseña actualizada correctamente.',
        ]);
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
                $usuario->username . '". Roles anteriores: "' .
                $rolesAnterioresTexto . '". Roles nuevos: "' .
                $rolesNuevosTexto . '".',
            entity: $usuario
        );

        $usuario->load('roles');

        return response()->json([
            'success' => true,
            'mensaje' => 'Roles del usuario actualizados correctamente.',
            'usuario' => app(UsuarioService::class)->presentar($usuario),
        ]);
    }

    public function destroy(User $usuario)
    {
        if ($usuario->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No puedes eliminar tu propia cuenta.',
            ], 422);
        }

        $nombre = $usuario->username;
        $persona = $usuario->name;
        $id = $usuario->id;

        AuditLogService::log(
            module: 'usuarios',
            action: 'ELIMINAR_USUARIO',
            description: 'Se eliminó el usuario "' . $nombre .
                '" (' . $persona . '). La persona se conserva sin usuario.',
            entity: $usuario
        );

        $usuario->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'Usuario eliminado correctamente.',
            'id' => $id,
        ]);
    }

    private function urls(User $usuario): array
    {
        return [
            'update' => route('usuarios.update', $usuario),
            'estado' => route('usuarios.estado', $usuario),
            'password' => route('usuarios.password', $usuario),
            'roles' => route('usuarios.roles', $usuario),
            'delete' => route('usuarios.destroy', $usuario),
        ];
    }
}
