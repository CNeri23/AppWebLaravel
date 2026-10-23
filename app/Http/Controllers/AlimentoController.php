<?php

namespace App\Http\Controllers;

use App\Models\Alimento;
use App\Models\Submodulo;
use App\Services\PermissionService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlimentoController extends Controller
{
    public function index(): View
    {
        $alimentos = Alimento::query()->orderBy('nombre')->get();
        $submodulo = Submodulo::with(['acciones' => fn ($query) => $query->where('activo', true)->orderBy('orden')])
            ->where('slug', 'alimentos')->where('activo', true)->first();
        $permisos = app(PermissionService::class);
        $accionesAlimentos = $submodulo?->acciones->filter(
            fn ($accion) => $permisos->tieneAccion(auth()->user(), $accion->id)
        ) ?? collect();
        return view('alimentacion.alimentos', compact('alimentos', 'accionesAlimentos'));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $alimento = Alimento::create($datos);
        AuditLogService::log('alimentacion', 'CREAR_ALIMENTO', 'Se creó el alimento "'.$alimento->nombre.'".', $alimento);
        if ($request->expectsJson()) return response()->json(['success' => true, 'alimento' => $alimento, 'mensaje' => 'Alimento creado correctamente.']);
        return redirect()->route('alimentacion.alimentos.index')->with('success', 'Alimento creado correctamente.');
    }

    public function update(Request $request, Alimento $alimento)
    {
        $datos = $this->validar($request, $alimento);
        $alimento->update($datos);
        $alimento->refresh();
        AuditLogService::log('alimentacion', 'EDITAR_ALIMENTO', 'Se actualizó el alimento "'.$alimento->nombre.'".', $alimento);
        if ($request->expectsJson()) return response()->json(['success' => true, 'alimento' => $alimento, 'mensaje' => 'Alimento actualizado correctamente.']);
        return redirect()->route('alimentacion.alimentos.index')->with('success', 'Alimento actualizado correctamente.');
    }

    public function destroy(Request $request, Alimento $alimento)
    {
        if ($alimento->detallesComida()->exists()) {
            $alimento->update(['activo' => false]);
            if ($request->expectsJson()) return response()->json(['success' => true, 'id' => $alimento->id, 'mensaje' => 'El alimento tiene registros históricos y se desactivó para conservarlos.']);
            return redirect()->route('alimentacion.alimentos.index')->with('success', 'El alimento tiene registros históricos y se desactivó para conservarlos.');
        }

        $nombre = $alimento->nombre;
        $alimento->delete();
        AuditLogService::log('alimentacion', 'ELIMINAR_ALIMENTO', 'Se eliminó el alimento "'.$nombre.'".', $alimento);
        if ($request->expectsJson()) return response()->json(['success' => true, 'id' => $alimento->id, 'mensaje' => 'Alimento eliminado correctamente.']);
        return redirect()->route('alimentacion.alimentos.index')->with('success', 'Alimento eliminado correctamente.');
    }

    private function validar(Request $request, ?Alimento $alimento = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150', Rule::unique('alimentos', 'nombre')->ignore($alimento?->id)],
            'categoria' => ['nullable', 'string', 'max:80'],
            'unidad_base' => ['required', Rule::in(['g', 'ml'])],
            'nombre_porcion' => ['nullable', 'string', 'max:80'],
            'cantidad_porcion' => ['nullable', 'numeric', 'gt:0', 'required_with:nombre_porcion'],
            'calorias' => ['required', 'numeric', 'min:0', 'max:99999'],
            'proteinas' => ['required', 'numeric', 'min:0', 'max:99999'],
            'carbohidratos' => ['required', 'numeric', 'min:0', 'max:99999'],
            'grasas' => ['required', 'numeric', 'min:0', 'max:99999'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }
}
