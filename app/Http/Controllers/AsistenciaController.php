<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Persona;
use App\Models\Submodulo;
use App\Services\AsistenciaService;
use App\Services\AuditLogService;
use App\Services\PermissionService;
use App\Services\SystemSettings;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AsistenciaController extends Controller
{
    private const LIMITE_REGISTROS = 3000;

    public function __construct(
        private AsistenciaService $asistencias,
        private SystemSettings $settings
    ) {
    }

    public function index(Request $request): View
    {
        $hoy = $this->asistencias->hoy();

        $desde = $this->fechaValida($request->query('desde')) ?? $hoy;
        $hasta = $this->fechaValida($request->query('hasta')) ?? $desde;

        if ($hasta < $desde) {
            $hasta = $desde;
        }

        $registros = Asistencia::query()
            ->with(['persona', 'membresia.plan', 'usuario'])
            ->whereBetween('fecha', [$desde, $hasta])
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->limit(self::LIMITE_REGISTROS)
            ->get();

        $permitidas = $registros->where('permitido', true);

        $resumen = [
            'entradas' => $permitidas->count(),
            'denegadas' => $registros->where('permitido', false)->count(),
            'miembros' => $permitidas->pluck('persona_id')->unique()->count(),
        ];

        $registros = $registros
            ->map(fn (Asistencia $asistencia) => $this->presentar($asistencia))
            ->values();

        $accionesAsistencias = $this->accionesPermitidas();

        $incluyeHoy = $hoy >= $desde && $hoy <= $hasta;

        $formatoFecha = (string) $this->settings->get('date_format', 'd/m/Y');

        $etiquetaRango = $desde === $hasta
            ? ($desde === $hoy ? 'Hoy' : Carbon::parse($desde)->format($formatoFecha))
            : Carbon::parse($desde)->format($formatoFecha) . ' — ' . Carbon::parse($hasta)->format($formatoFecha);

        return view('asistencias.index', compact(
            'registros',
            'resumen',
            'accionesAsistencias',
            'desde',
            'hasta',
            'hoy',
            'incluyeHoy',
            'etiquetaRango'
        ));
    }

    public function buscar(Request $request): JsonResponse
    {
        $busqueda = trim((string) $request->query('q', ''));

        if (mb_strlen($busqueda) < 1) {
            return response()->json([
                'success' => true,
                'miembros' => [],
            ]);
        }

        $formato = (string) $this->settings->get('date_format', 'd/m/Y');
        $hoy = $this->asistencias->hoy();

        $miembros = $this->asistencias
            ->buscarMiembros($busqueda)
            ->map(function (Persona $persona) use ($hoy, $formato) {
                $evaluacion = $this->asistencias->evaluarAcceso($persona, $hoy);
                $membresia = $evaluacion['membresia'];

                return [
                    'id' => $persona->id,
                    'nombre' => $this->asistencias->nombreCompleto($persona),
                    'email' => $persona->email,
                    'telefono' => $persona->telefono,
                    'plan' => $membresia?->plan?->nombre,
                    'vence' => $membresia?->fecha_fin?->format($formato),
                    'permitido' => $evaluacion['permitido'],
                    'estado' => $evaluacion['estado'],
                    'motivo' => $evaluacion['motivo'],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'miembros' => $miembros,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'persona_id' => [
                'required',
                'integer',
                'exists:personas,id',
            ],
        ], [
            'persona_id.required' => 'Selecciona un miembro.',
            'persona_id.integer' => 'El miembro seleccionado no es válido.',
            'persona_id.exists' => 'El miembro seleccionado no existe.',
        ]);

        $resultado = $this->asistencias->registrar(
            (int) $datos['persona_id'],
            auth()->id()
        );

        /** @var Asistencia $asistencia */
        $asistencia = $resultado['asistencia'];
        $duplicada = $resultado['duplicada'];
        $presentada = $this->presentar($asistencia);

        $nombre = $presentada['nombre'];

        if ($duplicada) {
            return response()->json([
                'success' => true,
                'resultado' => 'duplicada',
                'mensaje' => 'Ya registró su entrada hoy a las ' . $presentada['hora'] . '.',
                'miembro' => $this->resumenMiembro($asistencia),
                'asistencia' => null,
            ]);
        }

        if ($asistencia->permitido) {
            AuditLogService::log(
                module: 'asistencias',
                action: 'REGISTRAR',
                description: 'Se registró la entrada de "' . $nombre . '" (asistencia #' . $asistencia->id . ').',
                entity: $asistencia
            );
        } else {
            AuditLogService::log(
                module: 'asistencias',
                action: 'ACCESO_DENEGADO',
                description: 'Se denegó el acceso a "' . $nombre . '": ' . $asistencia->motivo,
                entity: $asistencia
            );
        }

        return response()->json([
            'success' => true,
            'resultado' => $asistencia->permitido ? 'permitido' : 'denegado',
            'mensaje' => $asistencia->permitido
                ? 'Acceso permitido.'
                : $asistencia->motivo,
            'miembro' => $this->resumenMiembro($asistencia),
            'asistencia' => $presentada,
        ]);
    }

    public function destroy(Asistencia $asistencia): JsonResponse
    {
        $asistencia->load('persona');

        $nombre = $asistencia->persona
            ? $this->asistencias->nombreCompleto($asistencia->persona)
            : 'miembro';

        $permitido = $asistencia->permitido;
        $id = $asistencia->id;

        AuditLogService::log(
            module: 'asistencias',
            action: 'ANULAR',
            description: 'Se anuló el registro #' . $id . ' de "' . $nombre . '" (' .
            ($permitido ? 'entrada' : 'acceso denegado') . ').',
            entity: $asistencia
        );

        $asistencia->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'Registro anulado correctamente.',
            'id' => $id,
            'permitido' => $permitido,
        ]);
    }

    /**
     * Datos listos para pintar en la tabla (vista y JS).
     */
    private function presentar(Asistencia $asistencia): array
    {
        $formatoFecha = (string) $this->settings->get('date_format', 'd/m/Y');
        $formatoHora = (string) $this->settings->get('time_format', 'H:i');

        $local = $this->asistencias->enZonaLocal($asistencia->fecha_hora);

        return [
            'id' => $asistencia->id,
            'persona_id' => $asistencia->persona_id,
            'timestamp' => $asistencia->fecha_hora->timestamp,
            'fecha' => $local->format($formatoFecha),
            'hora' => $local->format($formatoHora),
            'nombre' => $asistencia->persona
                ? $this->asistencias->nombreCompleto($asistencia->persona)
                : '—',
            'email' => $asistencia->persona?->email,
            'plan' => $asistencia->membresia?->plan?->nombre,
            'permitido' => $asistencia->permitido,
            'motivo' => $asistencia->motivo,
            'usuario' => $asistencia->usuario?->name,
            'url_eliminar' => route('asistencias.destroy', $asistencia),
        ];
    }

    private function resumenMiembro(Asistencia $asistencia): array
    {
        $formato = (string) $this->settings->get('date_format', 'd/m/Y');

        return [
            'id' => $asistencia->persona_id,
            'nombre' => $asistencia->persona
                ? $this->asistencias->nombreCompleto($asistencia->persona)
                : '—',
            'plan' => $asistencia->membresia?->plan?->nombre,
            'vence' => $asistencia->membresia?->fecha_fin?->format($formato),
        ];
    }

    private function accionesPermitidas()
    {
        $submodulo = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            },
        ])
            ->where('slug', 'asistencias')
            ->where('activo', true)
            ->first();

        if (!$submodulo) {
            return collect();
        }

        $permissionService = app(PermissionService::class);

        return $submodulo->acciones
            ->filter(function ($accion) use ($permissionService) {
                return $permissionService->tieneAccion(
                    auth()->user(),
                    $accion->id
                );
            })
            ->values();
    }

    private function fechaValida(mixed $valor): ?string
    {
        if (!is_string($valor) || $valor === '') {
            return null;
        }

        try {
            $fecha = Carbon::createFromFormat('Y-m-d', $valor);
        } catch (\Throwable) {
            return null;
        }

        return $fecha && $fecha->format('Y-m-d') === $valor
            ? $valor
            : null;
    }
}