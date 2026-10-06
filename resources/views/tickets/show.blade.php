@extends('layouts.app')
@section('content')

    @php
        $moneda = $moneda ?? [
            'codigo' => 'MXN',
            'simbolo' => '$',
            'tasa' => 1,
        ];

        $membresia = $ticket->pago?->membresia;
        $persona = $membresia?->persona;
        $plan = $membresia?->plan;
        $pago = $ticket->pago;

        $nombreMiembro = $persona
            ? trim(
                $persona->nombre . ' ' .
                $persona->apellido_paterno . ' ' .
                ($persona->apellido_materno ?? '')
            )
            : '—';

        $metodoPago = match ($pago?->metodo_pago) {
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia',
            default => ucfirst($pago?->metodo_pago ?? '—'),
        };

    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">
                Ticket
            </h1>

            <p class="text-secondary mb-0">
                Comprobante de pago
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('tickets.print', $ticket) }}" target="_blank" class="btn btn-primary">
                <i class="fa-solid fa-print me-2"></i>
                Imprimir
            </a>

            <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                <i class="fa-solid fa-arrow-left me-2"></i>
                Volver
            </button>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">

            <div class="card border-0 shadow-sm ticket-card">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center pb-4 border-bottom">
                        @if (!empty($datos['logo_path']))
                            <img src="{{ asset('storage/' . $datos['logo_path']) }}" alt="{{ $datos['system_name'] }}"
                                class="ticket-logo mb-3">
                        @endif

                        <h2 class="fw-bold mb-1">
                            {{ $datos['system_name'] }}
                        </h2>

                        <div class="text-secondary">
                            Comprobante de pago
                        </div>

                        <div class="mt-3">
                            <span class="small text-secondary d-block">
                                Folio
                            </span>

                            <span class="fw-bold fs-5">
                                {{ $ticket->folio }}
                            </span>
                        </div>

                        @if ($ticket->fecha_hora_emision_formateada)
                            <div class="small text-secondary mt-1">
                                {{ $ticket->fecha_hora_emision_formateada }}
                            </div>
                        @endif
                    </div>

                    <div class="py-4 border-bottom">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-user me-2"></i>
                            Cliente
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-7">
                                <div class="small text-secondary mb-1">
                                    Nombre
                                </div>

                                <div class="fw-semibold">
                                    {{ $nombreMiembro }}
                                </div>
                            </div>

                            @if ($persona?->email)
                                <div class="col-md-5">
                                    <div class="small text-secondary mb-1">
                                        Correo electrónico
                                    </div>

                                    <div>
                                        {{ $persona->email }}
                                    </div>
                                </div>
                            @endif

                            @if ($persona?->telefono)
                                <div class="col-md-5">
                                    <div class="small text-secondary mb-1">
                                        Teléfono
                                    </div>

                                    <div>
                                        {{ $persona->telefono }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="py-4 border-bottom">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-receipt me-2"></i>
                            Concepto
                        </h6>

                        <div class="ticket-concepto">
                            <div>
                                <div class="fw-semibold">
                                    {{ $plan?->nombre ?? 'Pago' }}
                                </div>

                                @if ($membresia?->fecha_inicio_formateada && $membresia?->fecha_fin_formateada)
                                    <div class="small text-secondary mt-1">
                                        Periodo:
                                        {{ $membresia->fecha_inicio_formateada }}
                                        —
                                        {{ $membresia->fecha_fin_formateada }}
                                    </div>
                                @endif
                            </div>

                            <div class="text-end">
                                <div class="small text-secondary">
                                    Importe
                                </div>

                                <div class="fw-bold">
                                    {{ $moneda['simbolo'] }}{{ number_format((float) ($pago?->monto_mostrado ?? 0), 2) }}
                                    {{ $moneda['codigo'] }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="py-4 border-bottom">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-credit-card me-2"></i>
                            Información del pago
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="small text-secondary mb-1">
                                    Método de pago
                                </div>

                                <div class="fw-semibold">
                                    {{ $metodoPago }}
                                </div>
                            </div>

                            @if ($pago?->referencia)
                                <div class="col-md-6">
                                    <div class="small text-secondary mb-1">
                                        Referencia
                                    </div>

                                    <div class="fw-semibold">
                                        {{ $pago->referencia }}
                                    </div>
                                </div>
                            @endif

                            @if ($pago?->fecha_hora_pago_formateada)
                                <div class="col-md-6">
                                    <div class="small text-secondary mb-1">
                                        Fecha de pago
                                    </div>

                                    <div>
                                        {{ $pago->fecha_hora_pago_formateada }}
                                    </div>
                                </div>
                            @endif

                            @if ($pago?->monto_recibido !== null)
                                <div class="col-md-6">
                                    <div class="small text-secondary mb-1">
                                        Monto recibido
                                    </div>

                                    <div>
                                        {{ $moneda['simbolo'] }}{{ number_format((float) $pago->monto_recibido_mostrado, 2) }}
                                        {{ $moneda['codigo'] }}
                                    </div>
                                </div>
                            @endif

                            @if ($pago?->cambio !== null)
                                <div class="col-md-6">
                                    <div class="small text-secondary mb-1">
                                        Cambio
                                    </div>

                                    <div>
                                        {{ $moneda['simbolo'] }}{{ number_format((float) $pago->cambio_mostrado, 2) }}
                                        {{ $moneda['codigo'] }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="py-4 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">
                                Total pagado
                            </span>

                            <span class="fw-bold fs-4">
                                {{ $moneda['simbolo'] }}{{ number_format((float) ($pago?->monto_mostrado ?? 0), 2) }}
                                {{ $moneda['codigo'] }}
                            </span>
                        </div>
                    </div>

                    <div class="pt-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="small text-secondary mb-1">
                                    Atendido por
                                </div>

                                <div class="fw-semibold">
                                    {{ $ticket->usuario?->name ?? '—' }}
                                </div>
                            </div>

                            @if ($ticket->sesionCaja)
                                <div class="col-md-6">
                                    <div class="small text-secondary mb-1">
                                        Sesión de caja
                                    </div>

                                    <div class="fw-semibold">
                                        #{{ $ticket->sesionCaja->id }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($ticket->observaciones)
                            <div class="mt-4">
                                <div class="small text-secondary mb-1">
                                    Observaciones
                                </div>

                                <div class="ticket-observaciones">
                                    {{ $ticket->observaciones }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="text-center pt-4 mt-4 border-top">
                        <div class="small text-secondary">
                            Este documento es un comprobante de la operación registrada.
                        </div>

                        <div class="small text-secondary mt-1">
                            {{ $datos['system_name'] }}
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

@endsection