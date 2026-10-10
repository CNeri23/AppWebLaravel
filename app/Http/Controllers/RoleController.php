<?php

namespace App\Http\Controllers;

use App\Models\Accion;
use App\Models\Modulo;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\Submodulo;
use App\Services\AuditLogService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{

    public function index(): View
    {
        $roles = Role::orderBy('name')->get();

        $rolesDelUsuario = auth()->user()
            ->roles()
            ->pluck('roles.id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $esSuperadministrador = $this->esSuperadministrador(auth()->user());
        $rolSuperadministradorId = (int) (Role::query()
            ->where('is_superadmin', true)
            ->value('id') ?? 0);

        $submoduloRoles = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'roles')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesRoles = collect();

        if ($submoduloRoles) {
            $accionesRoles = $submoduloRoles->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        return view('roles.index', compact(
            'roles',
            'accionesRoles',
            'rolesDelUsuario',
            'esSuperadministrador',
            'rolSuperadministradorId'
        ));
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
                'permisos' => route('roles.permisos', $rol),
            ],
        ]);
    }

    public function update(Request $request, Role $rol)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name,' . $rol->id],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($rol->is_superadmin && $datos['name'] !== $rol->name) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El nombre del rol Superadministrador está reservado y no se puede cambiar.',
            ], 422);
        }

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
                'permisos' => route('roles.permisos', $rol),
            ],
        ]);
    }

    public function destroy(Role $rol)
    {
        if ($rol->is_superadmin) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El rol Superadministrador está reservado y no se puede eliminar.',
            ], 422);
        }

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

    public function permisos(Role $rol)
    {
        if (!$this->puedeAdministrarPermisosDeRol(auth()->user(), $rol)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No puedes consultar ni administrar los permisos de este rol.',
            ], 403);
        }

        $modulos = Modulo::with([
            'submodulos' => function ($query) {
                $query
                    ->where('activo', true)
                    ->with([
                        'acciones' => function ($query) {
                            $query
                                ->where('activo', true)
                                ->orderBy('orden')
                                ->orderBy('nombre');
                        },
                    ])
                    ->orderBy('orden')
                    ->orderBy('nombre');
            },
        ])
            ->where('activo', true)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $permisos = RolePermission::query()
            ->where('role_id', $rol->id)
            ->get([
                'permission_type',
                'permission_id',
            ]);

        return response()->json([
            'success' => true,
            'modulos' => $modulos,
            'permisos' => $permisos,
        ]);
    }

    public function actualizarPermisos(Request $request, Role $rol)
    {
        if (!$this->puedeAdministrarPermisosDeRol($request->user(), $rol)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Por seguridad, no puedes modificar los permisos de este rol.',
            ], 403);
        }

        $datos = $request->validate([
            'permisos' => ['present', 'array'],

            'permisos.*.permission_type' => [
                'required',
                'string',
                'in:modulo,submodulo,accion',
            ],

            'permisos.*.permission_id' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $permisos = collect($datos['permisos'])
            ->unique(function ($permiso) {
                return $permiso['permission_type'] . ':' . $permiso['permission_id'];
            })
            ->values();

        foreach ($permisos as $permiso) {

            $existe = match ($permiso['permission_type']) {

                'modulo' => Modulo::query()
                    ->where('id', $permiso['permission_id'])
                    ->exists(),

                'submodulo' => Submodulo::query()
                    ->where('id', $permiso['permission_id'])
                    ->exists(),

                'accion' => Accion::query()
                    ->where('id', $permiso['permission_id'])
                    ->exists(),

                default => false,
            };

            if (!$existe) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'Uno de los permisos seleccionados no existe.',
                ], 422);
            }
        }

        DB::transaction(function () use ($rol, $permisos) {

            RolePermission::where('role_id', $rol->id)->delete();

            foreach ($permisos as $permiso) {
                RolePermission::create([
                    'role_id' => $rol->id,
                    'permission_type' => $permiso['permission_type'],
                    'permission_id' => $permiso['permission_id'],
                ]);
            }
        });

        AuditLogService::log(
            module: 'roles',
            action: 'ASIGNAR_PERMISOS',
            description: 'Se actualizaron los permisos del rol "' .
            $rol->name . '". Total de permisos asignados: ' .
            $permisos->count() . '.',
            entity: $rol
        );

        $permissionService = app(PermissionService::class);

        $submoduloRoles = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'roles')
            ->where('activo', true)
            ->first();

        $accionesRoles = collect();

        if ($submoduloRoles) {
            $accionesRoles = $submoduloRoles->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->map(function ($accion) {
                    return [
                        'id' => $accion->id,
                        'nombre' => $accion->nombre,
                        'slug' => $accion->slug,
                        'icono' => $accion->icono,
                    ];
                })
                ->values();
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Permisos actualizados correctamente.',
            'total_permisos' => $permisos->count(),
            'acciones_roles' => $accionesRoles,
        ]);
    }

    /**
     * Solo Superadministrador puede administrar roles que también tiene asignados,
     * excepto su propio rol privilegiado.
     */
    private function puedeAdministrarPermisosDeRol($usuario, Role $rol): bool
    {
        if (!$this->usuarioTieneRol($usuario, $rol)) {
            return true;
        }

        return $this->esSuperadministrador($usuario) && !$rol->is_superadmin;
    }

    private function usuarioTieneRol($usuario, Role $rol): bool
    {
        return $usuario->roles()
            ->where('roles.id', $rol->id)
            ->exists();
    }

    private function esSuperadministrador($usuario): bool
    {
        return $usuario->roles()
            ->where('roles.is_superadmin', true)
            ->exists();
    }
}
