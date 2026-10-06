<?php

namespace App\Services;

use App\Models\MovimientoCaja;
use App\Models\Pago;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TicketService
{
    public function __construct(
        private SystemSettings $settings
    ) {
    }

    public function crear(
        Pago $pago,
        ?string $observaciones = null
    ): Ticket {
        $zonaHoraria = $this->settings->get(
            'timezone',
            config('app.timezone')
        );

        return DB::transaction(function () use ($pago, $observaciones, $zonaHoraria) {
            $pago = Pago::query()
                ->lockForUpdate()
                ->find($pago->id);

            if (!$pago) {
                throw ValidationException::withMessages([
                    'pago_id' => 'El pago seleccionado no existe.',
                ]);
            }

            $ticketExistente = Ticket::query()
                ->where('pago_id', $pago->id)
                ->lockForUpdate()
                ->first();

            if ($ticketExistente) {
                return $ticketExistente->load([
                    'pago.membresia.persona',
                    'pago.membresia.plan',
                    'usuario',
                    'sesionCaja',
                ]);
            }

            $sesionCajaId = MovimientoCaja::query()
                ->where('pago_id', $pago->id)
                ->value('sesion_caja_id');

            $ahora = Carbon::now($zonaHoraria);

            $ticket = Ticket::create([
                'folio' => 'TMP-' . Str::uuid(),
                'pago_id' => $pago->id,
                'usuario_id' => auth()->id(),
                'sesion_caja_id' => $sesionCajaId,
                'fecha_emision' => $ahora,
                'observaciones' => $observaciones,
            ]);

            $ticket->folio = sprintf(
                'TKT-%s-%06d',
                $ahora->format('Ymd'),
                $ticket->id
            );

            $ticket->save();

            $ticket->load([
                'pago.membresia.persona',
                'pago.membresia.plan',
                'usuario',
                'sesionCaja',
            ]);

            AuditLogService::log(
                module: 'tickets',
                action: 'GENERAR_TICKET',
                description: 'Se generó el ticket "' .
                    $ticket->folio .
                    '" correspondiente al pago #' .
                    $pago->id .
                    '.',
                entity: $ticket
            );

            return $ticket;
        });
    }
}