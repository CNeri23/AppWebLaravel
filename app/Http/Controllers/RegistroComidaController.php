<?php

namespace App\Http\Controllers;

use App\Models\Alimento;
use App\Models\RegistroComida;
use App\Models\Submodulo;
use App\Services\PermissionService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistroComidaController extends Controller
{
    public function index(Request $request): View
    {
        $fecha = $request->date('fecha')?->toDateString() ?? now()->toDateString();
        $registros = RegistroComida::with('detalles')
            ->where('usuario_id', auth()->id())
            ->whereDate('fecha', $fecha)
            ->orderBy('created_at')
            ->get();
        $alimentos = Alimento::where('activo', true)->orderBy('nombre')->get();
        $totales = $this->totales($registros);
        $submodulo = Submodulo::with(['acciones' => fn ($query) => $query->where('activo', true)->orderBy('orden')])
            ->where('slug', 'registro-comidas')->where('activo', true)->first();
        $permisos = app(PermissionService::class);
        $accionesRegistros = $submodulo?->acciones->filter(
            fn ($accion) => $permisos->tieneAccion(auth()->user(), $accion->id)
        )->pluck('slug')->values() ?? collect();
        return view('alimentacion.registros', compact('registros', 'alimentos', 'fecha', 'totales', 'accionesRegistros'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date'],
            'tipo_comida' => ['required', 'in:desayuno,comida,cena,colacion'],
            'notas' => ['nullable', 'string', 'max:2000'],
            'alimentos' => ['required', 'array', 'min:1'],
            'alimentos.*.id' => ['required', 'integer', 'exists:alimentos,id'],
            'alimentos.*.cantidad' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'alimentos.*.unidad' => ['required', 'in:g,ml,porcion'],
        ]);

        $registro = DB::transaction(function () use ($datos) {
            $registro = RegistroComida::create([
                'usuario_id' => auth()->id(),
                'persona_id' => auth()->user()->persona?->id,
                'fecha' => $datos['fecha'],
                'tipo_comida' => $datos['tipo_comida'],
                'notas' => $datos['notas'] ?? null,
            ]);

            foreach ($datos['alimentos'] as $item) {
                $alimento = Alimento::where('activo', true)->findOrFail($item['id']);
                $cantidad = (float) $item['cantidad'];
                $unidad = $item['unidad'];
                if ($unidad === 'porcion') {
                    if (!$alimento->nombre_porcion || !$alimento->cantidad_porcion) {
                        throw ValidationException::withMessages(['alimentos' => 'Configura una porción para '.$alimento->nombre.' antes de registrarlo por porción.']);
                    }
                    $cantidadBase = $cantidad * (float) $alimento->cantidad_porcion;
                } else {
                    $cantidadBase = $cantidad;
                }

                if ($unidad !== 'porcion' && $unidad !== $alimento->unidad_base) {
                    throw ValidationException::withMessages(['alimentos' => 'La unidad de '.$alimento->nombre.' debe ser '.$alimento->unidad_base.' o porción.']);
                }

                $factor = $cantidadBase / 100;
                $registro->detalles()->create([
                    'alimento_id' => $alimento->id,
                    'nombre_alimento' => $alimento->nombre,
                    'cantidad' => $cantidad,
                    'unidad' => $unidad,
                    'calorias' => round((float) $alimento->calorias * $factor, 2),
                    'proteinas' => round((float) $alimento->proteinas * $factor, 2),
                    'carbohidratos' => round((float) $alimento->carbohidratos * $factor, 2),
                    'grasas' => round((float) $alimento->grasas * $factor, 2),
                ]);
            }
            return $registro;
        });

        AuditLogService::log('alimentacion', 'REGISTRAR_COMIDA', 'Se registró '.$registro->tipo_comida.' del '.$registro->fecha->format('d/m/Y').'.', $registro);
        return redirect()->route('alimentacion.registros.index', ['fecha' => $datos['fecha']])->with('success', 'Comida registrada correctamente.');
    }

    public function destroy(RegistroComida $registro)
    {
        abort_unless($registro->usuario_id === auth()->id(), 403);
        $registro->delete();
        return redirect()->route('alimentacion.registros.index')->with('success', 'Registro eliminado correctamente.');
    }

    private function totales($registros): array
    {
        return [
            'calorias' => $registros->sum(fn ($r) => $r->detalles->sum('calorias')),
            'proteinas' => $registros->sum(fn ($r) => $r->detalles->sum('proteinas')),
            'carbohidratos' => $registros->sum(fn ($r) => $r->detalles->sum('carbohidratos')),
            'grasas' => $registros->sum(fn ($r) => $r->detalles->sum('grasas')),
        ];
    }
}
