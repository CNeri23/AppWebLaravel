<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Tipo;
use App\Services\AuditLogService;
use App\Services\PermissionService;
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

        return view('miembros.index', compact(
            'miembros',
            'direcciones',
            'accionesMiembros'
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
}
