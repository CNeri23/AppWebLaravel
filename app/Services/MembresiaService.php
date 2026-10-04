<?php

namespace App\Services;

use App\Models\Membresia;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembresiaService
{
    public function __construct(
        private SystemSettings $settings
    ) {
    }

    public function contratar(
        int $personaId,
        int $planId,
        string $metodoPago,
        ?float $montoRecibido = null,
        ?string $referencia = null,
        ?string $observaciones = null
    ): array {
        $zonaHoraria = $this->settings->get(
            'timezone',
            config('app.timezone')
        );

        return DB::transaction(function () use ($personaId, $planId, $metodoPago, $montoRecibido, $referencia, $observaciones, $zonaHoraria) {
            $persona = Persona::with('tipos')
                ->lockForUpdate()
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

            $plan = Plan::query()
                ->lockForUpdate()
                ->find($planId);

            if (!$plan) {
                throw ValidationException::withMessages([
                    'plan_id' => 'El plan seleccionado no existe.',
                ]);
            }

            if (!$plan->activo) {
                throw ValidationException::withMessages([
                    'plan_id' => 'El plan seleccionado está inactivo.',
                ]);
            }

            if ($metodoPago === 'efectivo') {
                if (
                    $montoRecibido === null ||
                    $montoRecibido < (float) $plan->precio
                ) {
                    throw ValidationException::withMessages([
                        'monto_recibido' => 'El monto recibido debe ser igual o mayor al precio de la membresía.',
                    ]);
                }
            } else {
                $montoRecibido = null;
            }

            $ahora = now($zonaHoraria);
            $fechaInicio = $ahora->copy()->startOfDay();
            $fechaActual = $fechaInicio->toDateString();

            Membresia::query()
                ->where('persona_id', $persona->id)
                ->where('estado', Membresia::ESTADO_ACTIVA)
                ->whereDate('fecha_fin', '<', $fechaActual)
                ->lockForUpdate()
                ->get()
                ->each(function (Membresia $membresia) use ($ahora) {
                    $membresia->estado = Membresia::ESTADO_VENCIDA;
                    $membresia->updated_at = $ahora;
                    $membresia->save();
                });

            $membresiaActiva = Membresia::query()
                ->where('persona_id', $persona->id)
                ->where('estado', Membresia::ESTADO_ACTIVA)
                ->whereDate('fecha_fin', '>=', $fechaActual)
                ->lockForUpdate()
                ->first();

            if ($membresiaActiva) {
                throw ValidationException::withMessages([
                    'persona_id' => 'El miembro ya tiene una membresía activa.',
                ]);
            }

            $fechaFin = $fechaInicio->copy()
                ->addDays($plan->duracion_dias - 1);

            $membresia = Membresia::create([
                'persona_id' => $persona->id,
                'plan_id' => $plan->id,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
                'estado' => Membresia::ESTADO_ACTIVA,
                'precio' => $plan->precio,
                'observaciones' => $observaciones,
            ]);

            $pago = Pago::create([
                'membresia_id' => $membresia->id,
                'monto' => $plan->precio,
                'metodo_pago' => $metodoPago,
                'monto_recibido' => $montoRecibido,
                'cambio' => $montoRecibido !== null
                    ? round(
                        $montoRecibido - (float) $plan->precio,
                        2
                    )
                    : null,
                'referencia' => $referencia,
                'fecha_pago' => $ahora,
                'observaciones' => 'Pago inicial de la membresía.',
            ]);

            $membresia->load([
                'persona',
                'plan',
                'pagos',
            ]);

            return [
                'membresia' => $membresia,
                'pago' => $pago,
            ];
        });
    }

    public function actualizar(
        int $membresiaId,
        ?string $observaciones = null
    ): Membresia {
        $zonaHoraria = $this->settings->get(
            'timezone',
            config('app.timezone')
        );

        return DB::transaction(function () use ($membresiaId, $observaciones, $zonaHoraria) {
            $membresia = Membresia::query()
                ->lockForUpdate()
                ->find($membresiaId);

            if (!$membresia) {
                throw ValidationException::withMessages([
                    'membresia_id' => 'La membresía seleccionada no existe.',
                ]);
            }

            $membresia->observaciones =
                $observaciones;

            $membresia->updated_at =
                now($zonaHoraria);

            $membresia->save();

            $membresia->load([
                'persona',
                'plan',
                'pagos',
            ]);

            return $membresia;
        });
    }

    public function renovar(
        int $membresiaId,
        int $planId,
        string $metodoPago,
        ?float $montoRecibido = null,
        ?string $referencia = null,
        ?string $observaciones = null
    ): array {
        $zonaHoraria = $this->settings->get(
            'timezone',
            config('app.timezone')
        );

        return DB::transaction(function () use ($membresiaId, $planId, $metodoPago, $montoRecibido, $referencia, $observaciones, $zonaHoraria) {
            $membresia = Membresia::query()
                ->lockForUpdate()
                ->find($membresiaId);

            if (!$membresia) {
                throw ValidationException::withMessages([
                    'membresia_id' => 'La membresía seleccionada no existe.',
                ]);
            }

            if ($membresia->estado === Membresia::ESTADO_CANCELADA) {
                throw ValidationException::withMessages([
                    'membresia_id' => 'No se puede renovar una membresía cancelada.',
                ]);
            }

            $plan = Plan::query()
                ->lockForUpdate()
                ->find($planId);

            if (!$plan) {
                throw ValidationException::withMessages([
                    'plan_id' => 'El plan seleccionado no existe.',
                ]);
            }

            if (!$plan->activo) {
                throw ValidationException::withMessages([
                    'plan_id' => 'El plan seleccionado está inactivo.',
                ]);
            }

            if ($metodoPago === 'efectivo') {
                if (
                    $montoRecibido === null ||
                    $montoRecibido < (float) $plan->precio
                ) {
                    throw ValidationException::withMessages([
                        'monto_recibido' => 'El monto recibido debe ser igual o mayor al precio de la renovación.',
                    ]);
                }
            } else {
                $montoRecibido = null;
            }

            $ahora = now($zonaHoraria);
            $hoy = $ahora->copy()->startOfDay();

            $fechaFinActual = Carbon::parse(
                $membresia->fecha_fin,
                $zonaHoraria
            )->startOfDay();

            if ($membresia->estado === Membresia::ESTADO_ACTIVA) {
                $fechaInicio = $fechaFinActual->copy()->addDay();
            } else {
                $fechaInicio = $hoy;
            }

            $fechaFin = $fechaInicio->copy()
                ->addDays($plan->duracion_dias - 1);

            $membresia->plan_id =
                $plan->id;

            $membresia->forceFill([
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString(),
            ]);

            $membresia->estado =
                Membresia::ESTADO_ACTIVA;

            $membresia->precio =
                $plan->precio;

            if ($observaciones !== null) {
                $membresia->observaciones =
                    $observaciones;
            }

            $membresia->updated_at =
                $ahora;

            $membresia->save();

            $pago = Pago::create([
                'membresia_id' => $membresia->id,
                'monto' => $plan->precio,
                'metodo_pago' => $metodoPago,
                'monto_recibido' => $montoRecibido,
                'cambio' => $montoRecibido !== null
                    ? round(
                        $montoRecibido - (float) $plan->precio,
                        2
                    )
                    : null,
                'referencia' => $referencia,
                'fecha_pago' => $ahora,
                'observaciones' => 'Pago de renovación de la membresía.',
            ]);

            $membresia->load([
                'persona',
                'plan',
                'pagos',
            ]);

            return [
                'membresia' => $membresia,
                'pago' => $pago,
            ];
        });
    }

    public function cancelar(
        int $membresiaId
    ): Membresia {
        $zonaHoraria = $this->settings->get(
            'timezone',
            config('app.timezone')
        );

        return DB::transaction(function () use ($membresiaId, $zonaHoraria) {
            $membresia = Membresia::query()
                ->lockForUpdate()
                ->find($membresiaId);

            if (!$membresia) {
                throw ValidationException::withMessages([
                    'membresia_id' => 'La membresía seleccionada no existe.',
                ]);
            }

            if ($membresia->estado !== Membresia::ESTADO_ACTIVA) {
                throw ValidationException::withMessages([
                    'membresia_id' => 'Solo se pueden cancelar membresías activas.',
                ]);
            }

            $membresia->estado =
                Membresia::ESTADO_CANCELADA;

            $membresia->updated_at =
                now($zonaHoraria);

            $membresia->save();

            $membresia->load([
                'persona',
                'plan',
                'pagos',
            ]);

            return $membresia;
        });
    }
}