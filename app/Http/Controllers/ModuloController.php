<?php
namespace App\Http\Controllers;
use App\Models\Modulo;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
class ModuloController extends Controller
{
    public function index()
    {
        $modulos = Modulo::orderBy('orden')->orderBy('nombre')->get();
        return view('modulos.index', compact('modulos'));
    }
    public function store(Request $request)
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:100'], 'slug' => ['required', 'string', 'max:100', 'unique:modulos,slug'], 'descripcion' => ['nullable', 'string', 'max:255'], 'icono' => ['nullable', 'string', 'max:150'], 'orden' => ['required', 'integer', 'min:0'],]);
        $modulo = Modulo::create($datos);
        AuditLogService::log('MODULOS', 'CREAR_MODULO', 'Se creó el módulo "' . $modulo->nombre . '".', $modulo);
        return response()->json(['success' => true, 'mensaje' => 'Módulo creado correctamente.', 'modulo' => $modulo,]);
    }
    public function update(Request $request, Modulo $modulo)
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:100'], 'slug' => ['required', 'string', 'max:100', 'unique:modulos,slug,' . $modulo->id,], 'descripcion' => ['nullable', 'string', 'max:255'], 'icono' => ['nullable', 'string', 'max:150'], 'orden' => ['required', 'integer', 'min:0'],]);
        $cambios = [];
        foreach ($datos as $campo => $valorNuevo) {
            $valorAnterior = $modulo->getOriginal($campo);
            if ((string) $valorAnterior !== (string) $valorNuevo) {
                $cambios[] = $campo;
            }
        }
        $modulo->update($datos);
        if (!empty($cambios)) {
            AuditLogService::log('MODULOS', 'EDITAR_MODULO', 'Se actualizó el módulo "' . $modulo->nombre . '". Campos modificados: ' . implode(', ', $cambios) . '.', $modulo);
        }
        return response()->json(['success' => true, 'mensaje' => 'Módulo actualizado correctamente.', 'modulo' => $modulo,]);
    }
    public function toggle(Modulo $modulo)
    {
        $modulo->update(['activo' => !$modulo->activo,]);
        $accion = $modulo->activo ? 'ACTIVAR_MODULO' : 'DESACTIVAR_MODULO';
        $estado = $modulo->activo ? 'activado' : 'desactivado';
        AuditLogService::log('MODULOS', $accion, 'Se ' . $estado . ' el módulo "' . $modulo->nombre . '".', $modulo);
        return response()->json(['success' => true, 'activo' => $modulo->activo, 'mensaje' => $modulo->activo ? 'Módulo activado correctamente.' : 'Módulo desactivado correctamente.',]);
    }
    public function reorder(Request $request, Modulo $modulo)
    {
        $datos = $request->validate(['direccion' => ['required', 'in:arriba,abajo'],]);
        $vecino = $datos['direccion'] === 'arriba' ? Modulo::where('orden', '<', $modulo->orden)->orderByDesc('orden')->orderByDesc('id')->first() : Modulo::where('orden', '>', $modulo->orden)->orderBy('orden')->orderBy('id')->first();
        if (!$vecino) {
            return response()->json(['success' => false, 'mensaje' => 'El módulo ya está en ese extremo de la lista.',], 422);
        }
        $ordenModulo = $modulo->orden;
        $ordenVecino = $vecino->orden;
        if ($ordenModulo === $ordenVecino) {
            $ordenVecino = $datos['direccion'] === 'arriba' ? $ordenModulo - 1 : $ordenModulo + 1;
        }
        $modulo->update(['orden' => $ordenVecino,]);
        $vecino->update(['orden' => $ordenModulo,]);
        AuditLogService::log('MODULOS', 'REORDENAR_MODULO', 'Se movió el módulo "' . $modulo->nombre . '" ' . $datos['direccion'] . '.', $modulo);
        return response()->json(['success' => true, 'mensaje' => 'Orden actualizado correctamente.',]);
    }
    public function destroy(Modulo $modulo)
    {
        $nombreModulo = $modulo->nombre;
        AuditLogService::log('MODULOS', 'ELIMINAR_MODULO', 'Se eliminó el módulo "' . $nombreModulo . '".', $modulo);
        $modulo->delete();
        return response()->json(['success' => true, 'mensaje' => 'Módulo eliminado correctamente.',]);
    }
}