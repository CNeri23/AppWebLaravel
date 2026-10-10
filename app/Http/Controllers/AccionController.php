<?php

namespace App\Http\Controllers;

use App\Models\Submodulo;
use App\Models\Accion;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AccionController extends Controller
{
    public function store(Request $request, Submodulo $submodulo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:acciones,slug'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:150', 'regex:/^<i\s+class="\s*fa-(?:solid|regular|brands)(?:\s+fa-[a-z0-9]+(?:-[a-z0-9]+)*)+\s*"\s*>\s*<\/i>$/D'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        $accion = $submodulo->acciones()->create($datos);

        AuditLogService::log(
            'ACCIONES',
            'CREAR_ACCION',
            'Se creó la acción "' .
            $accion->nombre .
            '" en el submódulo "' .
            $submodulo->nombre .
            '".',
            $accion
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Acción creada correctamente.',
            'accion' => $accion,
        ]);
    }

    public function update(Request $request, Accion $accion)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'unique:acciones,slug,' . $accion->id,
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:150', 'regex:/^<i\s+class="\s*fa-(?:solid|regular|brands)(?:\s+fa-[a-z0-9]+(?:-[a-z0-9]+)*)+\s*"\s*>\s*<\/i>$/D'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        $cambios = [];

        foreach ($datos as $campo => $valorNuevo) {
            $valorAnterior = $accion->getOriginal($campo);

            if ((string) $valorAnterior !== (string) $valorNuevo) {
                $cambios[] = $campo;
            }
        }

        $submoduloNombre = $accion->submodulo?->nombre ?? 'Sin submódulo';

        $accion->update($datos);

        if (!empty($cambios)) {
            AuditLogService::log(
                'ACCIONES',
                'EDITAR_ACCION',
                'Se actualizó la acción "' .
                $accion->nombre .
                '" del submódulo "' .
                $submoduloNombre .
                '". Campos modificados: ' .
                implode(', ', $cambios) .
                '.',
                $accion
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Acción actualizada correctamente.',
            'accion' => $accion,
        ]);
    }

    public function toggle(Accion $accion)
    {
        $accion->update([
            'activo' => !$accion->activo,
        ]);

        $accionTipo = $accion->activo
            ? 'ACTIVAR_ACCION'
            : 'DESACTIVAR_ACCION';

        $estado = $accion->activo
            ? 'activada'
            : 'desactivada';

        $submoduloNombre = $accion->submodulo?->nombre ?? 'Sin submódulo';

        AuditLogService::log(
            'ACCIONES',
            $accionTipo,
            'Se ' .
            $estado .
            ' la acción "' .
            $accion->nombre .
            '" del submódulo "' .
            $submoduloNombre .
            '".',
            $accion
        );

        return response()->json([
            'success' => true,
            'activo' => $accion->activo,
            'mensaje' => $accion->activo
                ? 'Acción activada correctamente.'
                : 'Acción desactivada correctamente.',
        ]);
    }

    public function reorder(Request $request, Accion $accion)
    {
        $datos = $request->validate([
            'direccion' => ['required', 'in:arriba,abajo'],
        ]);

        $vecino = $datos['direccion'] === 'arriba'
            ? Accion::where('submodulo_id', $accion->submodulo_id)
                ->where('orden', '<', $accion->orden)
                ->orderByDesc('orden')
                ->orderByDesc('id')
                ->first()
            : Accion::where('submodulo_id', $accion->submodulo_id)
                ->where('orden', '>', $accion->orden)
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

        if (!$vecino) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La acción ya está en ese extremo de la lista.',
            ], 422);
        }

        $ordenActual = $accion->orden;
        $ordenVecino = $vecino->orden;

        if ($ordenActual === $ordenVecino) {
            $ordenVecino = $datos['direccion'] === 'arriba'
                ? $ordenActual - 1
                : $ordenActual + 1;
        }

        $accion->update([
            'orden' => $ordenVecino,
        ]);

        $vecino->update([
            'orden' => $ordenActual,
        ]);

        $submoduloNombre = $accion->submodulo?->nombre ?? 'Sin submódulo';

        AuditLogService::log(
            'ACCIONES',
            'REORDENAR_ACCION',
            'Se movió la acción "' .
            $accion->nombre .
            '" ' .
            $datos['direccion'] .
            ' dentro del submódulo "' .
            $submoduloNombre .
            '".',
            $accion
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Orden actualizado correctamente.',
        ]);
    }

    public function destroy(Accion $accion)
    {
        $submoduloNombre = $accion->submodulo?->nombre ?? 'Sin submódulo';
        $accionNombre = $accion->nombre;

        AuditLogService::log(
            'ACCIONES',
            'ELIMINAR_ACCION',
            'Se eliminó la acción "' .
            $accionNombre .
            '" del submódulo "' .
            $submoduloNombre .
            '".',
            $accion
        );

        $accion->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'Acción eliminada correctamente.',
        ]);
    }
}