<?php
namespace App\Http\Controllers;
use App\Models\Modulo;
use App\Models\Submodulo;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
class SubmoduloController extends Controller
{
    public function store(Request $request, Modulo $modulo)
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:100'], 'slug' => ['required', 'string', 'max:100', 'unique:submodulos,slug'], 'descripcion' => ['nullable', 'string', 'max:255'], 'icono' => ['nullable', 'string', 'max:150', 'regex:/^<i\s+class="\s*fa-(?:solid|regular|brands)(?:\s+fa-[a-z0-9]+(?:-[a-z0-9]+)*)+\s*"\s*>\s*<\/i>$/D'], 'ruta' => ['nullable', 'string', 'max:150'], 'orden' => ['required', 'integer', 'min:0'],]);
        $submodulo = $modulo->submodulos()->create($datos);
        AuditLogService::log('SUBMODULOS', 'CREAR_SUBMODULO', 'Se creó el submódulo "' . $submodulo->nombre . '" en el módulo "' . $modulo->nombre . '".', $submodulo);
        return response()->json(['success' => true, 'mensaje' => 'Submódulo creado correctamente.', 'submodulo' => $submodulo,]);
    }
    public function update(Request $request, Submodulo $submodulo)
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:100'], 'slug' => ['required', 'string', 'max:100', 'unique:submodulos,slug,' . $submodulo->id,], 'descripcion' => ['nullable', 'string', 'max:255'], 'icono' => ['nullable', 'string', 'max:150', 'regex:/^<i\s+class="\s*fa-(?:solid|regular|brands)(?:\s+fa-[a-z0-9]+(?:-[a-z0-9]+)*)+\s*"\s*>\s*<\/i>$/D'], 'ruta' => ['nullable', 'string', 'max:150'], 'orden' => ['required', 'integer', 'min:0'],]);
        $cambios = [];
        foreach ($datos as $campo => $valorNuevo) {
            $valorAnterior = $submodulo->getOriginal($campo);
            if ((string) $valorAnterior !== (string) $valorNuevo) {
                $cambios[] = $campo;
            }
        }
        $moduloNombre = $submodulo->modulo?->nombre ?? 'Sin módulo';
        $submodulo->update($datos);
        if (!empty($cambios)) {
            AuditLogService::log('SUBMODULOS', 'EDITAR_SUBMODULO', 'Se actualizó el submódulo "' . $submodulo->nombre . '" del módulo "' . $moduloNombre . '". Campos modificados: ' . implode(', ', $cambios) . '.', $submodulo);
        }
        return response()->json(['success' => true, 'mensaje' => 'Submódulo actualizado correctamente.', 'submodulo' => $submodulo,]);
    }
    public function toggle(Submodulo $submodulo)
    {
        $submodulo->update(['activo' => !$submodulo->activo,]);
        $accion = $submodulo->activo ? 'ACTIVAR_SUBMODULO' : 'DESACTIVAR_SUBMODULO';
        $estado = $submodulo->activo ? 'activado' : 'desactivado';
        $moduloNombre = $submodulo->modulo?->nombre ?? 'Sin módulo';
        AuditLogService::log('SUBMODULOS', $accion, 'Se ' . $estado . ' el submódulo "' . $submodulo->nombre . '" del módulo "' . $moduloNombre . '".', $submodulo);
        return response()->json(['success' => true, 'activo' => $submodulo->activo, 'mensaje' => $submodulo->activo ? 'Submódulo activado correctamente.' : 'Submódulo desactivado correctamente.',]);
    }
    public function reorder(Request $request, Submodulo $submodulo)
    {
        $datos = $request->validate(['direccion' => ['required', 'in:arriba,abajo'],]);
        $vecino = $datos['direccion'] === 'arriba' ? Submodulo::where('modulo_id', $submodulo->modulo_id)->where('orden', '<', $submodulo->orden)->orderByDesc('orden')->orderByDesc('id')->first() : Submodulo::where('modulo_id', $submodulo->modulo_id)->where('orden', '>', $submodulo->orden)->orderBy('orden')->orderBy('id')->first();
        if (!$vecino) {
            return response()->json(['success' => false, 'mensaje' => 'El submódulo ya está en ese extremo de la lista.',], 422);
        }
        $ordenActual = $submodulo->orden;
        $ordenVecino = $vecino->orden;
        if ($ordenActual === $ordenVecino) {
            $ordenVecino = $datos['direccion'] === 'arriba' ? $ordenActual - 1 : $ordenActual + 1;
        }
        $submodulo->update(['orden' => $ordenVecino,]);
        $vecino->update(['orden' => $ordenActual,]);
        $moduloNombre = $submodulo->modulo?->nombre ?? 'Sin módulo';
        AuditLogService::log('SUBMODULOS', 'REORDENAR_SUBMODULO', 'Se movió el submódulo "' . $submodulo->nombre . '" ' . $datos['direccion'] . ' dentro del módulo "' . $moduloNombre . '".', $submodulo);
        return response()->json(['success' => true, 'mensaje' => 'Orden actualizado correctamente.',]);
    }
    public function destroy(Submodulo $submodulo)
    {
        $moduloNombre = $submodulo->modulo?->nombre ?? 'Sin módulo';
        $submoduloNombre = $submodulo->nombre;
        AuditLogService::log('SUBMODULOS', 'ELIMINAR_SUBMODULO', 'Se eliminó el submódulo "' . $submoduloNombre . '" del módulo "' . $moduloNombre . '".', $submodulo);
        $submodulo->delete();
        return response()->json(['success' => true, 'mensaje' => 'Submódulo eliminado correctamente.',]);
    }
}