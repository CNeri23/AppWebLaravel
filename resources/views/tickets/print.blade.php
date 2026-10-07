@php
    $pago = $ticket->pago;
    $membresia = $pago?->membresia;
    $persona = $membresia?->persona;
    $plan = $membresia?->plan;

    $simbolo = $moneda['simbolo'] ?? '$';
    $codigo = $moneda['codigo'] ?? 'MXN';

    $dinero = fn($valor) => $simbolo . number_format((float) $valor, 2) . ' ' . $codigo;

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
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ticket {{ $ticket->folio }}</title>

    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }

        body {
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.35;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .ticket {
            width: 72mm;
            margin: 0 auto;
            padding: 4mm 0;
        }

        .centro {
            text-align: center;
        }

        .logo {
            display: block;
            max-width: 38mm;
            max-height: 22mm;
            margin: 0 auto 2mm;
            filter: grayscale(1);
        }

        .sistema {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .folio {
            font-size: 14px;
            font-weight: 700;
        }

        .linea {
            border-top: 1px dashed #000;
            margin: 3mm 0;
        }

        .titulo {
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }

        .fila {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 4px;
        }

        .fila span:last-child {
            text-align: right;
        }

        .etiqueta {
            font-size: 11px;
        }

        .total {
            font-size: 15px;
            font-weight: 700;
        }

        .pie {
            font-size: 11px;
        }

        @media screen {
            body {
                background: #e9ecef;
            }

            .ticket {
                background: #fff;
                margin: 16px auto;
                padding: 6mm 4mm;
                box-shadow: 0 2px 8px rgba(0, 0, 0, .2);
            }
        }
    </style>
</head>

<body>
    <div class="ticket">

        <div class="centro">
            @if (!empty($datos['logo_path']))
                <img src="{{ asset('storage/' . $datos['logo_path']) }}" alt="" class="logo">
            @endif

            <div class="sistema">{{ $datos['system_name'] }}</div>
            <div>Comprobante de pago</div>

            <div class="linea"></div>

            <div class="etiqueta">Folio</div>
            <div class="folio">{{ $ticket->folio }}</div>

            @if ($ticket->fecha_hora_emision_formateada)
                <div>{{ $ticket->fecha_hora_emision_formateada }}</div>
            @endif
        </div>

        <div class="linea"></div>

        <div class="titulo">Cliente</div>
        <div>{{ $nombreMiembro }}</div>

        @if ($persona?->telefono)
            <div class="etiqueta">Tel: {{ $persona->telefono }}</div>
        @endif

        <div class="linea"></div>

        <div class="titulo">Concepto</div>
        <div class="fila">
            <span>{{ $plan?->nombre ?? 'Pago' }}</span>
            <span>{{ $dinero($pago?->monto_mostrado ?? 0) }}</span>
        </div>

        @if ($membresia?->fecha_inicio_formateada && $membresia?->fecha_fin_formateada)
            <div class="etiqueta">
                Periodo: {{ $membresia->fecha_inicio_formateada }} - {{ $membresia->fecha_fin_formateada }}
            </div>
        @endif

        <div class="linea"></div>

        <div class="fila">
            <span>Método</span>
            <span>{{ $metodoPago }}</span>
        </div>

        @if ($pago?->referencia)
            <div class="fila">
                <span>Referencia</span>
                <span>{{ $pago->referencia }}</span>
            </div>
        @endif

        @if ($pago?->fecha_hora_pago_formateada)
            <div class="fila">
                <span>Fecha de pago</span>
                <span>{{ $pago->fecha_hora_pago_formateada }}</span>
            </div>
        @endif

        @if ($pago?->monto_recibido !== null)
            <div class="fila">
                <span>Recibido</span>
                <span>{{ $dinero($pago->monto_recibido_mostrado) }}</span>
            </div>
        @endif

        @if ($pago?->cambio !== null)
            <div class="fila">
                <span>Cambio</span>
                <span>{{ $dinero($pago->cambio_mostrado) }}</span>
            </div>
        @endif

        <div class="linea"></div>

        <div class="fila total">
            <span>TOTAL</span>
            <span>{{ $dinero($pago?->monto_mostrado ?? 0) }}</span>
        </div>

        <div class="linea"></div>

        <div class="pie">
            <div>Atendió: {{ $ticket->usuario?->name ?? '—' }}</div>

            @if ($ticket->sesionCaja)
                <div>Caja: #{{ $ticket->sesionCaja->id }}</div>
            @endif

            @if ($ticket->observaciones)
                <div>{{ $ticket->observaciones }}</div>
            @endif
        </div>

        <div class="linea"></div>

        <div class="centro pie">
            <div>Este documento es un comprobante</div>
            <div>de la operación registrada.</div>
            <div>¡Gracias por tu preferencia!</div>
        </div>

    </div>
</body>

</html>