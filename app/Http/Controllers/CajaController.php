<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Submodulo;
use App\Services\AuditLogService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CajaController extends Controller
{
    public function index()
    {
        $cajas = Caja::withCount('sesiones')
            ->with([
                'sesiones' => function ($query) {
                    $query
                        ->where('estado', 'abierta')
                        ->with('usuarioApertura')
                        ->latest('fecha_apertura');
                }
            ])
            ->orderBy('nombre')
            ->get();

        $submoduloCajas = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'cajas')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesCajas = collect();

        if ($submoduloCajas) {
            $accionesCajas = $submoduloCajas->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        return view('cajas.index', compact(
            'cajas',
            'accionesCajas'
        ));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:cajas,nombre',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], $this->mensajes());

        $caja = Caja::create([
            'nombre' => trim($datos['nombre']),
            'descripcion' => $this->normalizarDescripcion($datos['descripcion'] ?? null),
            'activo' => true,
        ]);

        AuditLogService::log(
            module: 'cajas',
            action: 'CREAR',
            description: 'Se creó la caja "' . $caja->nombre . '".',
            entity: $caja
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Caja creada correctamente.',
            'caja' => $this->formatearCaja($caja),
        ]);
    }

    public function show(Caja $caja)
    {
        $caja->load([
            'sesiones' => function ($query) {
                $query
                    ->with([
                        'usuarioApertura',
                        'usuarioCierre',
                        'usuarioAutorizacion',
                    ])
                    ->latest('fecha_apertura');
            }
        ]);

        return response()->json([
            'success' => true,
            'caja' => $caja,
        ]);
    }

    public function update(Request $request, Caja $caja)
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cajas', 'nombre')->ignore($caja->id),
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], $this->mensajes());

        $nombreNuevo = trim($datos['nombre']);
        $descripcionNueva = $this->normalizarDescripcion($datos['descripcion'] ?? null);

        $nombreAnterior = $caja->nombre;
        $descripcionAnterior = $this->normalizarDescripcion($caja->descripcion);

        $cambios = [];

        if ($nombreAnterior !== $nombreNuevo) {
            $cambios[] = 'Nombre: "' . $nombreAnterior . '" → "' . $nombreNuevo . '"';
        }

        if ($descripcionAnterior !== $descripcionNueva) {
            $cambios[] = 'Descripción actualizada';
        }

        if (empty($cambios)) {
            return response()->json([
                'success' => true,
                'sin_cambios' => true,
                'mensaje' => 'No hubo cambios para actualizar.',
                'caja' => $this->formatearCaja($caja),
            ]);
        }

        $caja->nombre = $nombreNuevo;
        $caja->descripcion = $descripcionNueva;
        $caja->save();

        AuditLogService::log(
            module: 'cajas',
            action: 'EDITAR',
            description: 'Se modificó la caja "' . $caja->nombre .
                '". Cambios: ' . implode(' | ', $cambios),
            entity: $caja
        );

        return response()->json([
            'success' => true,
            'sin_cambios' => false,
            'mensaje' => 'Caja actualizada correctamente.',
            'caja' => $this->formatearCaja($caja),
        ]);
    }

    public function cambiarEstado(Caja $caja)
    {
        if ($caja->activo && $caja->sesiones()->where('estado', 'abierta')->exists()) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se puede desactivar una caja con una sesión abierta.',
            ], 422);
        }

        $caja->activo = !$caja->activo;
        $caja->save();

        AuditLogService::log(
            module: 'cajas',
            action: $caja->activo ? 'ACTIVAR' : 'DESACTIVAR',
            description: 'Se ' . ($caja->activo ? 'activó' : 'desactivó') .
                ' la caja "' . $caja->nombre . '".',
            entity: $caja
        );

        return response()->json([
            'success' => true,
            'mensaje' => $caja->activo
                ? 'Caja activada correctamente.'
                : 'Caja desactivada correctamente.',
            'caja' => $this->formatearCaja($caja),
        ]);
    }

    private function formatearCaja(Caja $caja): array
    {
        $sesion = $caja->sesiones()
            ->where('estado', 'abierta')
            ->with('usuarioApertura')
            ->latest('fecha_apertura')
            ->first();

        return [
            'id' => $caja->id,
            'nombre' => $caja->nombre,
            'descripcion' => $caja->descripcion,
            'activo' => (bool) $caja->activo,
            'sesion_abierta' => $sesion
                ? [
                    'id' => $sesion->id,
                    'estado' => $sesion->estado,
                    'usuario_apertura' => [
                        'name' => optional($sesion->usuarioApertura)->name,
                    ],
                ]
                : null,
        ];
    }

    private function normalizarDescripcion(?string $descripcion): ?string
    {
        $descripcion = trim((string) $descripcion);

        return $descripcion === '' ? null : $descripcion;
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre de la caja es obligatorio.',
            'nombre.max' => 'El nombre de la caja no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe una caja con ese nombre.',
            'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
        ];
    }
}