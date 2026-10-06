<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\CurrencyConverter;
use App\Services\SystemSettings;
use Carbon\Carbon;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        private SystemSettings $settings,
        private CurrencyConverter $currencyConverter
    ) {
    }

    // El acceso se controla en routes/web.php con permiso:accion,membresias.ver

    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'pago.membresia.persona',
            'pago.membresia.plan',
            'usuario',
            'sesionCaja',
        ]);

        $datos = $this->prepararTicket($ticket);

        return view('tickets.show', [
            'ticket' => $ticket,
            'datos' => $datos,
            'moneda' => $datos['moneda'],
        ]);
    }

    public function print(Ticket $ticket): View
    {
        $ticket->load([
            'pago.membresia.persona',
            'pago.membresia.plan',
            'usuario',
            'sesionCaja',
        ]);

        $datos = $this->prepararTicket($ticket);

        return view('tickets.print', [
            'ticket' => $ticket,
            'datos' => $datos,
            'moneda' => $datos['moneda'],
        ]);
    }

    private function prepararTicket(Ticket $ticket): array
    {
        $zonaHoraria = $this->settings->get(
            'timezone',
            config('app.timezone')
        );

        $dateFormat = $this->settings->get(
            'date_format',
            'd/m/Y'
        );

        $timeFormat = $this->settings->get(
            'time_format',
            'H:i'
        );

        $codigoMoneda = $this->settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $simbolos = [
            'MXN' => '$',
            'USD' => '$',
            'EUR' => '€',
        ];

        $moneda = [
            'codigo' => $codigoMoneda,
            'simbolo' => $simbolos[$codigoMoneda] ?? $codigoMoneda,
            'tasa' => $this->currencyConverter->rate($codigoMoneda),
        ];

        $datos = [
            'system_name' => $this->settings->get(
                'system_name',
                'IronPulse'
            ),
            'logo_path' => $this->settings->get(
                'logo_path'
            ),
            'currency' => $codigoMoneda,
            'timezone' => $zonaHoraria,
            'date_format' => $dateFormat,
            'time_format' => $timeFormat,
            'moneda' => $moneda,
        ];

        $fechaEmision = $ticket->fecha_emision
            ? Carbon::parse($ticket->fecha_emision)
                ->setTimezone($zonaHoraria)
            : null;

        $ticket->fecha_emision_formateada = $fechaEmision
            ? $fechaEmision->format($dateFormat)
            : null;

        $ticket->hora_emision_formateada = $fechaEmision
            ? $fechaEmision->format($timeFormat)
            : null;

        $ticket->fecha_hora_emision_formateada = $fechaEmision
            ? $fechaEmision->format($dateFormat . ' ' . $timeFormat)
            : null;

        if ($ticket->pago?->fecha_pago) {
            $fechaPago = Carbon::parse($ticket->pago->fecha_pago)
                ->setTimezone($zonaHoraria);

            $ticket->pago->fecha_pago_formateada = $fechaPago->format(
                $dateFormat
            );

            $ticket->pago->hora_pago_formateada = $fechaPago->format(
                $timeFormat
            );

            $ticket->pago->fecha_hora_pago_formateada = $fechaPago->format(
                $dateFormat . ' ' . $timeFormat
            );
        }

        if ($ticket->pago) {
            $ticket->pago->monto_mostrado = $this->currencyConverter->fromBase(
                (float) $ticket->pago->monto,
                $codigoMoneda
            );

            $ticket->pago->monto_recibido_mostrado = $ticket->pago->monto_recibido !== null
                ? $this->currencyConverter->fromBase(
                    (float) $ticket->pago->monto_recibido,
                    $codigoMoneda
                )
                : null;

            $ticket->pago->cambio_mostrado = $ticket->pago->cambio !== null
                ? $this->currencyConverter->fromBase(
                    (float) $ticket->pago->cambio,
                    $codigoMoneda
                )
                : null;
        }

        if ($ticket->pago?->membresia?->fecha_inicio) {
            $ticket->pago->membresia->fecha_inicio_formateada = Carbon::parse(
                $ticket->pago->membresia->fecha_inicio
            )->format($dateFormat);
        }

        if ($ticket->pago?->membresia?->fecha_fin) {
            $ticket->pago->membresia->fecha_fin_formateada = Carbon::parse(
                $ticket->pago->membresia->fecha_fin
            )->format($dateFormat);
        }

        $ticket->fecha_emision = $fechaEmision;

        return $datos;
    }
}