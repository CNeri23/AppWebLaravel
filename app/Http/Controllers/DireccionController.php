<?php

namespace App\Http\Controllers;

use App\Models\Direccion;
use App\Models\Submodulo;
use App\Services\AuditLogService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DireccionController extends Controller
{
    public function index(): View
    {
        $direcciones = Direccion::withCount('personas')
            ->orderBy('calle')
            ->orderBy('numero_exterior')
            ->orderBy('colonia')
            ->get();

        $submoduloDirecciones = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'direcciones')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesDirecciones = collect();

        if ($submoduloDirecciones) {
            $accionesDirecciones = $submoduloDirecciones->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        return view('direcciones.index', compact(
            'direcciones',
            'accionesDirecciones'
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'calle' => ['required', 'string', 'max:150'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['required', 'string', 'max:100'],
            'codigo_postal' => ['required', 'string', 'max:5'],
            'municipio' => ['required', 'string', 'max:100'],
            'estado' => ['required', 'string', 'max:100'],
            'pais' => ['required', 'string', 'max:100'],
        ]);

        $direccion = DB::transaction(function () use ($datos) {
            return Direccion::create([
                'calle' => $datos['calle'],
                'numero_exterior' => $datos['numero_exterior'],
                'numero_interior' => $datos['numero_interior'] ?? null,
                'colonia' => $datos['colonia'],
                'codigo_postal' => $datos['codigo_postal'],
                'municipio' => $datos['municipio'],
                'estado' => $datos['estado'],
                'pais' => $datos['pais'],
            ]);
        });

        $direccion->loadCount('personas');

        AuditLogService::log(
            module: 'direcciones',
            action: 'CREAR_DIRECCION',
            description: 'Se creó la dirección "' .
                $direccion->calle . ' ' .
                $direccion->numero_exterior .
                '".',
            entity: $direccion
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Dirección creada correctamente.',
            'direccion' => $direccion,
            'urls' => [
                'update' => route('direcciones.update', $direccion),
                'delete' => route('direcciones.destroy', $direccion),
            ],
        ]);
    }

    public function update(Request $request, Direccion $direccion)
    {
        $datos = $request->validate([
            'calle' => ['required', 'string', 'max:150'],
            'numero_exterior' => ['required', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['required', 'string', 'max:100'],
            'codigo_postal' => ['required', 'string', 'max:5'],
            'municipio' => ['required', 'string', 'max:100'],
            'estado' => ['required', 'string', 'max:100'],
            'pais' => ['required', 'string', 'max:100'],
        ]);

        $resultado = DB::transaction(function () use ($datos, $direccion) {
            $calleAnterior = $direccion->calle;
            $numeroExteriorAnterior = $direccion->numero_exterior;
            $numeroInteriorAnterior = $direccion->numero_interior;
            $coloniaAnterior = $direccion->colonia;
            $codigoPostalAnterior = $direccion->codigo_postal;
            $municipioAnterior = $direccion->municipio;
            $estadoAnterior = $direccion->estado;
            $paisAnterior = $direccion->pais;

            $direccion->calle = $datos['calle'];
            $direccion->numero_exterior = $datos['numero_exterior'];
            $direccion->numero_interior = $datos['numero_interior'] ?? null;
            $direccion->colonia = $datos['colonia'];
            $direccion->codigo_postal = $datos['codigo_postal'];
            $direccion->municipio = $datos['municipio'];
            $direccion->estado = $datos['estado'];
            $direccion->pais = $datos['pais'];
            $direccion->save();

            $cambios = [];

            if ($calleAnterior !== $direccion->calle) {
                $cambios[] = 'calle';
            }

            if ($numeroExteriorAnterior !== $direccion->numero_exterior) {
                $cambios[] = 'número exterior';
            }

            if ($numeroInteriorAnterior !== $direccion->numero_interior) {
                $cambios[] = 'número interior';
            }

            if ($coloniaAnterior !== $direccion->colonia) {
                $cambios[] = 'colonia';
            }

            if ($codigoPostalAnterior !== $direccion->codigo_postal) {
                $cambios[] = 'código postal';
            }

            if ($municipioAnterior !== $direccion->municipio) {
                $cambios[] = 'municipio';
            }

            if ($estadoAnterior !== $direccion->estado) {
                $cambios[] = 'estado';
            }

            if ($paisAnterior !== $direccion->pais) {
                $cambios[] = 'país';
            }

            $direccion->loadCount('personas');

            return [
                'direccion' => $direccion,
                'cambios' => $cambios,
            ];
        });

        if (!empty($resultado['cambios'])) {
            AuditLogService::log(
                module: 'direcciones',
                action: 'EDITAR_DIRECCION',
                description: 'Se actualizó la dirección "' .
                    $resultado['direccion']->calle . ' ' .
                    $resultado['direccion']->numero_exterior .
                    '". Campos modificados: ' .
                    implode(', ', $resultado['cambios']) . '.',
                entity: $resultado['direccion']
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => empty($resultado['cambios'])
                ? 'No hubo cambios para actualizar.'
                : 'Dirección actualizada correctamente.',
            'direccion' => $resultado['direccion'],
            'cambios' => $resultado['cambios'],
        ]);
    }

    public function destroy(Direccion $direccion)
    {
        $personasAsignadas = $direccion->personas()->count();

        if ($personasAsignadas > 0) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se puede eliminar esta dirección porque está asignada a ' .
                    $personasAsignadas . ' ' .
                    ($personasAsignadas === 1 ? 'persona.' : 'personas.'),
            ], 422);
        }

        $descripcion = trim(
            $direccion->calle . ' ' .
            $direccion->numero_exterior .
            ($direccion->numero_interior
                ? ' Int. ' . $direccion->numero_interior
                : '') .
            ' — ' .
            $direccion->colonia
        );

        DB::transaction(function () use ($direccion) {
            $direccion->delete();
        });

        AuditLogService::log(
            module: 'direcciones',
            action: 'ELIMINAR_DIRECCION',
            description: 'Se eliminó la dirección "' .
                $descripcion .
                '".',
            entity: $direccion
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Dirección eliminada correctamente.',
            'id' => $direccion->id,
        ]);
    }
}