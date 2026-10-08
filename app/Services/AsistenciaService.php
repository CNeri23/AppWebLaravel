<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Membresia;
use App\Models\Persona;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsistenciaService
{
    public const ACCESO_VIGENTE = 'vigente';
    public const ACCESO_VENCIDA = 'vencida';
    public const ACCESO_CANCELADA = 'cancelada';
    public const ACCESO_PENDIENTE = 'pendiente';
    public const ACCESO_SIN_MEMBRESIA = 'sin_membresia';

    public function __construct(
        private SystemSettings $settings
    ) {
    }

    public function zonaHoraria(): string
    {
        return (string) $this->settings->get(
            'timezone',
            config('app.timezone')
        );
    }

    /**
     * Fecha de hoy (Y-m-d) según la zona horaria configurada en el sistema.
     */
    public function hoy(): string
    {
        return now($this->zonaHoraria())->toDateString();
    }

    /**
     * Busca miembros por nombre, apellidos, teléfono, correo o número (id).
     * Cada palabra escrita debe coincidir con algún campo.
     */
    public function buscarMiembros(string $busqueda, int $limite = 8): Collection
    {
        $palabras = preg_split('/\s+/', trim($busqueda), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($palabras === []) {
            return collect();
        }

        $consulta = Persona::query()
            ->whereHas('tipos', function ($query) {
                $query->where('nombre', 'Miembro');
            })
            ->with($this->relacionMembresias());

        foreach ($palabras as $palabra) {
            $like = '%' . addcslashes($palabra, '%_\\') . '%';

            $consulta->where(function ($grupo) use ($palabra, $like) {
                $grupo->where('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhere('telefono', 'like', $like)
                    ->orWhere('email', 'like', $like);

                if (ctype_digit($palabra) && strlen($palabra) <= 6) {
                    $grupo->orWhere('id', (int) $palabra);
                }
            });
        }

        return $consulta
            ->orderBy('nombre')
            ->orderBy('apellido_paterno')
            ->limit($limite)
            ->get();
    }

    /**
     * Revisa si el miembro puede entrar hoy.
     *
     * @return array{permitido: bool, estado: string, motivo: ?string, membresia: ?Membresia}
     */
    public function evaluarAcceso(Persona $persona, ?string $hoy = null): array
    {
        $hoy ??= $this->hoy();
        $formato = (string) $this->settings->get('date_format', 'd/m/Y');

        if (!$persona->relationLoaded('membresias')) {
            $persona->load($this->relacionMembresias());
        }

        $membresias = $persona->membresias;

        $vigente = $membresias->first(function (Membresia $membresia) use ($hoy) {
            return $membresia->estado === Membresia::ESTADO_ACTIVA
                && $membresia->fecha_inicio->toDateString() <= $hoy
                && $membresia->fecha_fin->toDateString() >= $hoy;
        });

        if ($vigente) {
            return [
                'permitido' => true,
                'estado' => self::ACCESO_VIGENTE,
                'motivo' => null,
                'membresia' => $vigente,
            ];
        }

        $ultima = $membresias->first();

        if (!$ultima) {
            return [
                'permitido' => false,
                'estado' => self::ACCESO_SIN_MEMBRESIA,
                'motivo' => 'No tiene ninguna membresía registrada.',
                'membresia' => null,
            ];
        }

        if ($ultima->estado === Membresia::ESTADO_CANCELADA) {
            return [
                'permitido' => false,
                'estado' => self::ACCESO_CANCELADA,
                'motivo' => 'Su membresía fue cancelada.',
                'membresia' => $ultima,
            ];
        }

        if (
            $ultima->estado === Membresia::ESTADO_ACTIVA
            && $ultima->fecha_inicio->toDateString() > $hoy
        ) {
            return [
                'permitido' => false,
                'estado' => self::ACCESO_PENDIENTE,
                'motivo' => 'Su membresía inicia el ' . $ultima->fecha_inicio->format($formato) . '.',
                'membresia' => $ultima,
            ];
        }

        return [
            'permitido' => false,
            'estado' => self::ACCESO_VENCIDA,
            'motivo' => 'Su membresía venció el ' . $ultima->fecha_fin->format($formato) . '.',
            'membresia' => $ultima,
        ];
    }

    /**
     * Registra la entrada de un miembro.
     * - Si no tiene membresía vigente se guarda el intento como denegado.
     * - Solo se permite una entrada por día: si ya entró hoy no se duplica.
     *
     * @return array{asistencia: Asistencia, duplicada: bool, evaluacion: array}
     */
    public function registrar(int $personaId, ?int $usuarioId = null): array
    {
        return DB::transaction(function () use ($personaId, $usuarioId) {
            $persona = Persona::query()
                ->lockForUpdate()
                ->with(['tipos'])
                ->find($personaId);

            if (!$persona) {
                throw ValidationException::withMessages([
                    'persona_id' => 'El miembro seleccionado no existe.',
                ]);
            }

            $esMiembro = $persona->tipos->contains(function ($tipo) {
                return $tipo->nombre === 'Miembro';
            });

            if (!$esMiembro) {
                throw ValidationException::withMessages([
                    'persona_id' => 'La persona seleccionada no está registrada como miembro.',
                ]);
            }

            $persona->load($this->relacionMembresias());

            $hoy = $this->hoy();
            $evaluacion = $this->evaluarAcceso($persona, $hoy);

            if ($evaluacion['permitido']) {
                $existente = Asistencia::query()
                    ->with(['persona', 'membresia.plan', 'usuario'])
                    ->where('persona_id', $persona->id)
                    ->whereDate('fecha', $hoy)
                    ->where('permitido', true)
                    ->orderBy('fecha_hora')
                    ->first();

                if ($existente) {
                    return [
                        'asistencia' => $existente,
                        'duplicada' => true,
                        'evaluacion' => $evaluacion,
                    ];
                }
            }

            $asistencia = Asistencia::create([
                'persona_id' => $persona->id,
                'membresia_id' => $evaluacion['membresia']?->id,
                'usuario_id' => $usuarioId,
                'fecha' => $hoy,
                'fecha_hora' => now(),
                'permitido' => $evaluacion['permitido'],
                'motivo' => $evaluacion['motivo'],
            ]);

            $asistencia->load(['persona', 'membresia.plan', 'usuario']);

            return [
                'asistencia' => $asistencia,
                'duplicada' => false,
                'evaluacion' => $evaluacion,
            ];
        });
    }

    public function nombreCompleto(Persona $persona): string
    {
        return trim(
            $persona->nombre . ' ' .
            $persona->apellido_paterno . ' ' .
            ($persona->apellido_materno ?? '')
        );
    }

    /**
     * Fecha y hora en la zona horaria del sistema.
     */
    public function enZonaLocal(Carbon $fechaHora): Carbon
    {
        return $fechaHora->copy()->setTimezone($this->zonaHoraria());
    }

    private function relacionMembresias(): array
    {
        return [
            'membresias' => function ($query) {
                $query
                    ->with('plan')
                    ->orderByDesc('fecha_fin')
                    ->orderByDesc('id');
            },
        ];
    }
}