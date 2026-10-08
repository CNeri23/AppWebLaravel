<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Tipo;
use App\Services\AuditLogService;
use App\Services\PasswordPolicy;
use App\Services\PermissionService;
use App\Services\UsuarioService;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MiembroController extends Controller
{
    public function index(): View
    {
        $tipoMiembro = Tipo::where('nombre', 'Miembro')->first();

        $miembros = Persona::with([
            'direccion',
            'tipos',
        ])
            ->when($tipoMiembro, function ($query) use ($tipoMiembro) {
                $query->whereHas('tipos', function ($query) use ($tipoMiembro) {
                    $query->where('tipos.id', $tipoMiembro->id);
                });
            })
            ->orderBy('nombre')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->get();

        $direcciones = \App\Models\Direccion::query()
            ->orderBy('calle')
            ->orderBy('numero_exterior')
            ->orderBy('colonia')
            ->get();

        $submoduloMiembros = \App\Models\Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'miembros')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesMiembros = collect();

        if ($submoduloMiembros) {
            $accionesMiembros = $submoduloMiembros->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        // Usuarios que todavía no están ligados a ninguna persona (para vincular)
        $usuariosLibres = $accionesMiembros->contains('slug', 'miembros.usuario')
            ? User::query()->doesntHave('persona')->orderBy('username')->get(['id', 'username'])
            : collect();

        $politica = app(PasswordPolicy::class);

        $politicaPassword = [
            'min' => $politica->minLength(),
            'complex' => $politica->requiresComplexity(),
            'descripcion' => $politica->description(),
        ];

        return view('miembros.index', compact(
            'miembros',
            'direcciones',
            'accionesMiembros',
            'usuariosLibres',
            'politicaPassword'
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                'unique:personas,email',
            ],
            'direccion_id' => [
                'required',
                'integer',
                'exists:direcciones,id',
            ],
        ]);

        $resultado = DB::transaction(function () use ($datos) {
            $tipoMiembro = Tipo::where('nombre', 'Miembro')->first();

            if (!$tipoMiembro) {
                throw new \RuntimeException(
                    'El tipo "Miembro" no está configurado en el sistema.'
                );
            }

            $persona = Persona::create([
                'nombre' => $datos['nombre'],
                'apellido_paterno' => $datos['apellido_paterno'],
                'apellido_materno' => $datos['apellido_materno'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'email' => $datos['email'] ?? null,
                'direccion_id' => $datos['direccion_id'],
            ]);

            $persona->tipos()->attach($tipoMiembro->id);

            $persona->load([
                'direccion',
                'tipos',
            ]);

            return $persona;
        });

        AuditLogService::log(
            module: 'miembros',
            action: 'CREAR_MIEMBRO',
            description: 'Se creó el miembro "' .
                $resultado->nombre . ' ' .
                $resultado->apellido_paterno .
                '".',
            entity: $resultado
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Miembro creado correctamente.',
            'miembro' => $resultado,
            'urls' => [
                'update' => route('miembros.update', $resultado),
                'delete' => route('miembros.destroy', $resultado),
                'usuario' => route('miembros.usuario', $resultado),
            ],
        ]);
    }

    public function update(Request $request, Persona $miembro)
    {
        $tipoMiembro = Tipo::where('nombre', 'Miembro')->first();

        if (
            !$tipoMiembro ||
            !$miembro->tipos()->where('tipos.id', $tipoMiembro->id)->exists()
        ) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El registro indicado no pertenece al módulo de miembros.',
            ], 404);
        }

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('personas', 'email')->ignore($miembro->id),
            ],
            'direccion_id' => [
                'required',
                'integer',
                'exists:direcciones,id',
            ],
        ]);

        $resultado = DB::transaction(function () use ($datos, $miembro) {
            $nombreAnterior = $miembro->nombre;
            $apellidoPaternoAnterior = $miembro->apellido_paterno;
            $apellidoMaternoAnterior = $miembro->apellido_materno;
            $telefonoAnterior = $miembro->telefono;
            $correoAnterior = $miembro->email;
            $direccionAnterior = $miembro->direccion_id;

            $miembro->nombre = $datos['nombre'];
            $miembro->apellido_paterno = $datos['apellido_paterno'];
            $miembro->apellido_materno = $datos['apellido_materno'] ?? null;
            $miembro->telefono = $datos['telefono'] ?? null;
            $miembro->email = $datos['email'] ?? null;
            $miembro->direccion_id = $datos['direccion_id'];
            $miembro->save();

            $miembro->load([
                'direccion',
                'tipos',
            ]);

            $cambios = [];

            if ($nombreAnterior !== $miembro->nombre) {
                $cambios[] = 'nombre';
            }

            if ($apellidoPaternoAnterior !== $miembro->apellido_paterno) {
                $cambios[] = 'apellido paterno';
            }

            if ($apellidoMaternoAnterior !== $miembro->apellido_materno) {
                $cambios[] = 'apellido materno';
            }

            if ($telefonoAnterior !== $miembro->telefono) {
                $cambios[] = 'teléfono';
            }

            if ($correoAnterior !== $miembro->email) {
                $cambios[] = 'correo electrónico';
            }

            if ((int) $direccionAnterior !== (int) $miembro->direccion_id) {
                $cambios[] = 'dirección';
            }

            return [
                'miembro' => $miembro,
                'cambios' => $cambios,
            ];
        });

        if (!empty($resultado['cambios'])) {
            AuditLogService::log(
                module: 'miembros',
                action: 'EDITAR_MIEMBRO',
                description: 'Se actualizó el miembro "' .
                    $resultado['miembro']->nombre . ' ' .
                    $resultado['miembro']->apellido_paterno .
                    '". Campos modificados: ' .
                    implode(', ', $resultado['cambios']) . '.',
                entity: $resultado['miembro']
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => empty($resultado['cambios'])
                ? 'No hubo cambios para actualizar.'
                : 'Miembro actualizado correctamente.',
            'miembro' => $resultado['miembro'],
            'cambios' => $resultado['cambios'],
        ]);
    }

    public function destroy(Persona $miembro)
    {
        $tipoMiembro = Tipo::where('nombre', 'Miembro')->first();

        if (
            !$tipoMiembro ||
            !$miembro->tipos()->where('tipos.id', $tipoMiembro->id)->exists()
        ) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El registro indicado no pertenece al módulo de miembros.',
            ], 404);
        }

        $nombre = trim(
            $miembro->nombre . ' ' .
            $miembro->apellido_paterno . ' ' .
            ($miembro->apellido_materno ?? '')
        );

        DB::transaction(function () use ($miembro, $tipoMiembro) {
            $miembro->tipos()->detach($tipoMiembro->id);
        });

        AuditLogService::log(
            module: 'miembros',
            action: 'ELIMINAR_MIEMBRO',
            description: 'Se eliminó el miembro "' .
                $nombre . '".',
            entity: $miembro
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Miembro eliminado correctamente.',
            'id' => $miembro->id,
        ]);
    }

    /**
     * Crea (o vincula) el usuario con el que un miembro entra al sistema.
     * El usuario se inserta primero y luego se guarda su id en la persona.
     */
    public function asignarUsuario(
        Request $request,
        Persona $miembro,
        PasswordPolicy $politica,
        UsuarioService $servicio
    ) {
        if ($miembro->usuario_id) {
            return response()->json([
                'success' => false,
                'mensaje' => 'Esta persona ya tiene un usuario asignado.',
            ], 422);
        }

        $modo = $request->input('modo') === 'vincular' ? 'vincular' : 'crear';

        $nombre = trim($miembro->nombre . ' ' . $miembro->apellido_paterno);

        if ($modo === 'vincular') {
            $datos = $request->validate([
                'usuario_id' => [
                    'required',
                    'integer',
                    'exists:users,id',
                    function (string $atributo, mixed $valor, \Closure $fallo) {
                        if (Persona::where('usuario_id', $valor)->exists()) {
                            $fallo('Ese usuario ya está asignado a otra persona.');
                        }
                    },
                ],
            ], [
                'usuario_id.required' => 'Selecciona un usuario.',
                'usuario_id.exists' => 'El usuario seleccionado no existe.',
            ]);

            $usuario = User::findOrFail($datos['usuario_id']);

            $miembro->usuario_id = $usuario->id;
            $miembro->save();

            AuditLogService::log(
                module: 'miembros',
                action: 'VINCULAR_USUARIO',
                description: 'Se vinculó el usuario "' . $usuario->username .
                    '" con el miembro "' . $nombre . '".',
                entity: $miembro
            );
        } else {
            $request->merge([
                'username' => Str::lower(trim((string) $request->input('username'))),
            ]);

            $datos = $request->validate([
                'username' => $servicio->reglasUsername(),
                'password' => $politica->rules(),
                'activo' => ['nullable', 'boolean'],
            ], [
                ...$servicio->mensajesUsername(),
                ...$politica->messages(),
            ]);

            $rol = $servicio->rolPredeterminado();

            $usuario = $servicio->crearParaPersona(
                $miembro,
                [
                    'username' => $datos['username'],
                    'password' => $datos['password'],
                    'activo' => $request->boolean('activo', true),
                ],
                $rol ? [$rol->id] : []
            );

            AuditLogService::log(
                module: 'miembros',
                action: 'CREAR_USUARIO_MIEMBRO',
                description: 'Se creó el usuario "' . $usuario->username .
                    '" para el miembro "' . $nombre . '".',
                entity: $miembro
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => $modo === 'vincular'
                ? 'Usuario vinculado correctamente.'
                : 'Usuario creado correctamente.',
            'miembro' => $miembro->fresh(['direccion', 'tipos']),
            'usuario' => [
                'id' => $usuario->id,
                'username' => $usuario->username,
            ],
        ]);
    }
}
