<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Submodulo;
use App\Services\AuditLogService;
use App\Services\CurrencyConverter;
use App\Services\PermissionService;
use App\Services\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $planes = Plan::query()
            ->orderBy('nombre')
            ->get();

        $submoduloPlanes = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'planes')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesPlanes = collect();

        if ($submoduloPlanes) {
            $accionesPlanes = $submoduloPlanes->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        $codigoMoneda = (string) app(SystemSettings::class)->get('currency', 'MXN');

        $simbolos = [
            'MXN' => '$',
            'USD' => 'US$',
            'EUR' => '€',
        ];

        $moneda = [
            'codigo' => $codigoMoneda,
            'simbolo' => $simbolos[$codigoMoneda] ?? $codigoMoneda,
            'tasa' => app(CurrencyConverter::class)->rate($codigoMoneda),
        ];

        return view('planes.index', compact(
            'planes',
            'accionesPlanes',
            'moneda'
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:planes,nombre',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'duracion_dias' => [
                'required',
                'integer',
                'min:1',
            ],
            'precio' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
        ]);

        $precioBase = $this->precioABase((float) $datos['precio']);

        $plan = DB::transaction(function () use ($datos, $precioBase) {
            return Plan::create([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'duracion_dias' => $datos['duracion_dias'],
                'precio' => $precioBase,
                'activo' => true,
            ]);
        });

        AuditLogService::log(
            module: 'planes',
            action: 'CREAR_PLAN',
            description: 'Se creó el plan "' .
            $plan->nombre .
            '".',
            entity: $plan
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Plan creado correctamente.',
            'plan' => $plan,
            'urls' => [
                'update' => route('planes.update', $plan),
                'delete' => route('planes.destroy', $plan),
            ],
        ]);
    }

    public function update(Request $request, Plan $plan)
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:planes,nombre,' . $plan->id,
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'duracion_dias' => [
                'required',
                'integer',
                'min:1',
            ],
            'precio' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
        ]);

        // El formulario envía el precio en la moneda configurada. Si no cambió
        // respecto a lo que se le mostró, se conserva el valor guardado para
        // no generar diferencias por redondeo de la conversión.
        $moneda = $this->monedaActual();
        $converter = app(CurrencyConverter::class);

        $precioBase = round($converter->fromBase((float) $plan->precio, $moneda), 2) === round((float) $datos['precio'], 2)
            ? (float) $plan->precio
            : $this->precioABase((float) $datos['precio']);

        $resultado = DB::transaction(function () use ($datos, $plan, $precioBase) {
            $nombreAnterior = $plan->nombre;
            $descripcionAnterior = $plan->descripcion;
            $duracionDiasAnterior = $plan->duracion_dias;
            $precioAnterior = $plan->precio;

            $plan->nombre = $datos['nombre'];
            $plan->descripcion = $datos['descripcion'] ?? null;
            $plan->duracion_dias = $datos['duracion_dias'];
            $plan->precio = $precioBase;
            $plan->save();

            $cambios = [];

            if ($nombreAnterior !== $plan->nombre) {
                $cambios[] = 'nombre';
            }

            if ($descripcionAnterior !== $plan->descripcion) {
                $cambios[] = 'descripción';
            }

            if ((int) $duracionDiasAnterior !== (int) $plan->duracion_dias) {
                $cambios[] = 'duración';
            }

            if ((float) $precioAnterior !== (float) $plan->precio) {
                $cambios[] = 'precio';
            }

            return [
                'plan' => $plan,
                'cambios' => $cambios,
            ];
        });

        if (!empty($resultado['cambios'])) {
            AuditLogService::log(
                module: 'planes',
                action: 'EDITAR_PLAN',
                description: 'Se actualizó el plan "' .
                $resultado['plan']->nombre .
                '". Campos modificados: ' .
                implode(', ', $resultado['cambios']) .
                '.',
                entity: $resultado['plan']
            );
        }

        return response()->json([
            'success' => true,
            'mensaje' => empty($resultado['cambios'])
                ? 'No hubo cambios para actualizar.'
                : 'Plan actualizado correctamente.',
            'plan' => $resultado['plan'],
            'cambios' => $resultado['cambios'],
        ]);
    }

    private function monedaActual(): string
    {
        return (string) app(SystemSettings::class)->get('currency', 'MXN');
    }

    private function precioABase(float $precio): float
    {
        $base = app(CurrencyConverter::class)->toBase($precio, $this->monedaActual());

        if ($base > 99999999.99) {
            throw ValidationException::withMessages([
                'precio' => 'El precio es demasiado alto.',
            ]);
        }

        return $base;
    }

    public function destroy(Plan $plan)
    {
        $nombre = $plan->nombre;

        DB::transaction(function () use ($plan) {
            $plan->delete();
        });

        AuditLogService::log(
            module: 'planes',
            action: 'ELIMINAR_PLAN',
            description: 'Se eliminó el plan "' .
            $nombre .
            '".',
            entity: $plan
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Plan eliminado correctamente.',
            'id' => $plan->id,
        ]);
    }

    public function toggleActivo(Plan $plan)
    {
        $estadoAnterior = $plan->activo;

        $plan->activo = !$plan->activo;
        $plan->save();

        AuditLogService::log(
            module: 'planes',
            action: $plan->activo
            ? 'ACTIVAR_PLAN'
            : 'INACTIVAR_PLAN',
            description: 'Se ' .
            ($plan->activo ? 'activó' : 'inactivó') .
            ' el plan "' .
            $plan->nombre .
            '".',
            entity: $plan
        );

        return response()->json([
            'success' => true,
            'mensaje' => $plan->activo
                ? 'Plan activado correctamente.'
                : 'Plan inactivado correctamente.',
            'plan' => $plan,
            'estado_anterior' => $estadoAnterior,
        ]);

    }
}