<?php

namespace App\Http\Controllers;

use App\Models\CategoriaFinanciera;
use App\Models\MovimientoFinanciero;
use App\Models\Submodulo;
use App\Services\AuditLogService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoriaFinancieraController extends Controller
{
    public function index(Request $request): View
    {
        $ambito = $request->query('ambito', 'personal');
        abort_unless(in_array($ambito, ['personal', 'gimnasio'], true), 404);

        $categorias = CategoriaFinanciera::query()
            ->where('ambito', $ambito)
            ->when($ambito === 'personal', fn ($query) => $query->where('user_id', auth()->id()))
            ->when($ambito === 'gimnasio', fn ($query) => $query->whereNull('user_id'))
            ->withCount('movimientos')
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        $submodulo = Submodulo::with(['acciones' => fn ($query) => $query->where('activo', true)->orderBy('orden')->orderBy('nombre')])
            ->where('slug', 'categorias')
            ->where('activo', true)
            ->first();

        $permisos = app(PermissionService::class);
        $accionesCategorias = $submodulo?->acciones->filter(
            fn ($accion) => $permisos->tieneAccion(auth()->user(), $accion->id)
        )->values() ?? collect();

        return view('finanzas.categorias.index', compact('categorias', 'accionesCategorias', 'ambito'));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $ambito = $datos['ambito'];
        $categoria = CategoriaFinanciera::create([
            'ambito' => $ambito,
            'user_id' => $ambito === 'personal' ? auth()->id() : null,
            'nombre' => trim($datos['nombre']),
            'tipo' => $datos['tipo'],
            'descripcion' => isset($datos['descripcion']) ? trim($datos['descripcion']) : null,
        ]);

        AuditLogService::log('finanzas', 'CREAR_CATEGORIA_FINANCIERA', 'Se creó la categoría financiera "' . $categoria->nombre . '".', $categoria);

        return response()->json([
            'success' => true,
            'mensaje' => 'Categoría creada correctamente.',
            'categoria' => $categoria->loadCount('movimientos'),
        ]);
    }

    public function update(Request $request, CategoriaFinanciera $categoria)
    {
        $this->autorizarAmbito($categoria);
        $datos = $this->validar($request, $categoria);

        $categoria->update([
            'nombre' => trim($datos['nombre']),
            'tipo' => $datos['tipo'],
            'descripcion' => isset($datos['descripcion']) ? trim($datos['descripcion']) : null,
        ]);

        AuditLogService::log('finanzas', 'EDITAR_CATEGORIA_FINANCIERA', 'Se actualizó la categoría financiera "' . $categoria->nombre . '".', $categoria);

        return response()->json([
            'success' => true,
            'mensaje' => 'Categoría actualizada correctamente.',
            'categoria' => $categoria->fresh()->loadCount('movimientos'),
        ]);
    }

    public function destroy(CategoriaFinanciera $categoria)
    {
        $this->autorizarAmbito($categoria);

        if ($categoria->movimientos()->exists()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No puedes eliminar esta categoría porque tiene movimientos asociados.',
            ], 422);
        }

        $nombre = $categoria->nombre;
        $id = $categoria->id;
        $categoria->delete();

        AuditLogService::log('finanzas', 'ELIMINAR_CATEGORIA_FINANCIERA', 'Se eliminó la categoría financiera "' . $nombre . '".', $categoria);

        return response()->json([
            'success' => true,
            'mensaje' => 'Categoría eliminada correctamente.',
            'id' => $id,
        ]);
    }

    private function validar(Request $request, ?CategoriaFinanciera $categoria = null): array
    {
        $ambito = $categoria?->ambito ?? $request->input('ambito');
        abort_unless(in_array($ambito, ['personal', 'gimnasio'], true), 422);

        $unico = Rule::unique('categorias_financieras', 'nombre')
            ->where('ambito', $ambito)
            ->where('tipo', $request->input('tipo'))
            ->where(function ($query) use ($ambito) {
                if ($ambito === 'personal') {
                    $query->where('user_id', auth()->id());
                } else {
                    $query->whereNull('user_id');
                }
            });

        if ($categoria) {
            $unico->ignore($categoria->id);
        }

        return $request->validate([
            'ambito' => [$categoria ? 'sometimes' : 'required', Rule::in(['personal', 'gimnasio'])],
            'nombre' => ['required', 'string', 'max:100', $unico],
            'tipo' => ['required', Rule::in(['ingreso', 'gasto'])],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function autorizarAmbito(CategoriaFinanciera $categoria): void
    {
        if ($categoria->ambito === 'personal') {
            abort_unless((int) $categoria->user_id === (int) auth()->id(), 404);
            return;
        }

        abort_unless($categoria->ambito === 'gimnasio' && $categoria->user_id === null, 404);
    }
}
