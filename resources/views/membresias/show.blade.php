@extends('layouts.app')
@section('content')

@php
    $accionesMembresias = $accionesMembresias ?? collect();
    $nombreMiembro = trim(
        $membresia->persona->nombre . ' ' .
        $membresia->persona->apellido_paterno . ' ' .
        ($membresia->persona->apellido_materno ?? '')
    );

    $estadoClase = match ($membresia->estado) {
        'activa' => 'text-bg-success',
        'vencida' => 'text-bg-secondary',
        'cancelada' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };

    $estadoNombre = match ($membresia->estado) {
        'activa' => 'Activa',
        'vencida' => 'Vencida',
        'cancelada' => 'Cancelada',
        default => ucfirst($membresia->estado),
    };

    $duracionDias = $membresia->duracion_dias ??
    ($membresia->fecha_inicio->diffInDays($membresia->fecha_fin) + 1);
    $precioMostrado = $membresia->precio_mostrado;
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="{{ route('membresias.index') }}" class="btn btn-sm btn-outline-secondary" title="Volver">
                <i class="fa-solid fa-arrow-left"></i>
            </a>

            <h1 class="fw-bold mb-0">
                Membresía
            </h1>
        </div>

        <p class="text-secondary mb-0">
            Detalle de la membresía y su historial de pagos.
        </p>
    </div>

    <div class="d-flex align-items-center gap-2">

        @foreach ($accionesMembresias as $accion)
            @if ($accion->slug === 'membresias.cancelar' && $membresia->estado === 'activa')
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                    data-bs-target="#modalCancelarMembresia">
                    {!! $accion->icono ?: '<i class="fa-solid fa-ban me-2"></i>' !!}
                    {{ $accion->nombre }}
                </button>
            @endif
        @endforeach
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <div class="text-secondary small mb-1">
                            Estado de la membresía
                        </div>

                        <span class="badge rounded-pill {{ $estadoClase }}">
                            {{ $estadoNombre }}
                        </span>
                    </div>

                    <div class="text-end">
                        <div class="text-secondary small mb-1">
                            Precio
                        </div>

                        <div class="fw-bold fs-4">
                            {{ number_format((float) $precioMostrado, 2) }}
                            {{ $membresia->moneda }}
                        </div>
                    </div>
                </div>

                <hr>
                <h5 class="fw-semibold mb-4">
                    <i class="fa-solid fa-id-card me-2"></i>
                    Información de la membresía
                </h5>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="text-secondary small mb-1">
                            Miembro
                        </div>

                        <div class="fw-semibold">
                            {{ $nombreMiembro }}
                        </div>

                        @if ($membresia->persona->email)
                            <div class="small text-secondary mt-1">
                                <i class="fa-solid fa-envelope me-1"></i>
                                {{ $membresia->persona->email }}
                            </div>
                        @endif

                        @if ($membresia->persona->telefono)
                            <div class="small text-secondary mt-1">
                                <i class="fa-solid fa-phone me-1"></i>
                                {{ $membresia->persona->telefono }}
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <div class="text-secondary small mb-1">
                            Plan
                        </div>

                        <div class="fw-semibold">
                            {{ $membresia->plan->nombre }}
                        </div>

                        @if ($membresia->plan->descripcion)
                            <div class="small text-secondary mt-1">
                                {{ $membresia->plan->descripcion }}
                            </div>
                        @endif
                    </div>

                    <div class="col-md-4">
                        <div class="text-secondary small mb-1">
                            Fecha de inicio
                        </div>

                        <div class="fw-semibold">
                            {{ $membresia->fecha_inicio_mostrada }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-secondary small mb-1">
                            Fecha de finalización
                        </div>

                        <div class="fw-semibold">
                            {{ $membresia->fecha_fin_mostrada }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-secondary small mb-1">
                            Duración
                        </div>

                        <div class="fw-semibold">
                            {{ $duracionDias }} días
                        </div>
                    </div>
                </div>

                @if ($membresia->observaciones)
                    <hr class="my-4">
                    <div>
                        <div class="text-secondary small mb-1">
                            Observaciones
                        </div>

                        <div>
                            {{ $membresia->observaciones }}
                        </div>
                    </div>
                @endif

            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="p-4 border-bottom">
                    <h5 class="fw-semibold mb-1">
                        <i class="fa-solid fa-money-bill-wave me-2"></i>
                        Historial de pagos
                    </h5>

                    <p class="text-secondary small mb-0">
                        Pagos registrados para esta membresía.
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="px-4"> Fecha</th>
                                <th> Método</th>
                                <th> Referencia</th>
                                <th> Monto</th>
                                <th class="text-center px-4"> Ticket</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($membresia->pagos as $pago)
                            <tr>
                                <td class="px-4">
                                    {{ $pago->fecha_pago_mostrada }}
                                </td>

                                <td>
                                    <span class="text-capitalize">
                                        {{ $pago->metodo_pago }}
                                    </span>
                                </td>

                                <td>
                                    @if ($pago->referencia)
                                        {{ $pago->referencia }}
                                        @else
                                        <span class="text-secondary">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span class="fw-semibold">
                                        {{ number_format((float) $pago->monto_mostrado, 2) }}
                                        {{ $pago->moneda }}
                                    </span>
                                </td>

                                <td class="text-center px-4">
                                    @if ($pago->ticket_url)
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary membresia-action-btn btn-ver-ticket"
                                            style="--bs-btn-color: var(--bs-body-color); --bs-btn-border-color: var(--bs-body-color); --bs-btn-hover-color: var(--bs-body-bg); --bs-btn-hover-bg: var(--bs-body-color); --bs-btn-hover-border-color: var(--bs-body-color); --bs-btn-active-color: var(--bs-body-bg); --bs-btn-active-bg: var(--bs-body-color); --bs-btn-active-border-color: var(--bs-body-color);"
                                            data-url="{{ $pago->ticket_url }}" data-tooltip="Ver ticket" title="Ver ticket">
                                            <i class="fa-solid fa-receipt"></i>
                                        </button>
                                    @else
                                        <span class="text-secondary">—</span>
                                    @endif
                                </td>
                            </tr>

                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-secondary">
                                        <i class="fa-solid fa-receipt fa-2x mb-3"></i>

                                        <p class="mb-0">
                                            No hay pagos registrados.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-semibold mb-4">
                    <i class="fa-solid fa-user me-2"></i>
                    Miembro
                </h5>

                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                        style="width: 52px; height: 52px;">
                        <i class="fa-solid fa-user"></i>
                    </div>

                    <div>
                        <div class="fw-semibold">
                            {{ $nombreMiembro }}
                        </div>

                        @if ($membresia->persona->email)
                            <div class="small text-secondary">
                                {{ $membresia->persona->email }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-grid">
                    <a href="{{ route('miembros.index') }}" class="btn btn-outline-primary">
                        <i class="fa-solid fa-eye me-2"></i>
                        Ver miembros
                    </a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-semibold mb-4">
                    <i class="fa-solid fa-receipt me-2"></i>
                    Resumen de pago
                </h5>

                @php
                    $pagoInicial = $membresia->pagos->first();
                @endphp

                @if ($pagoInicial)
                    <div class="mb-3">
                        <div class="text-secondary small mb-1">
                            Monto
                        </div>

                        <div class="fw-bold fs-5">
                            {{ number_format((float) $pagoInicial->monto_mostrado, 2) }}
                            {{ $pagoInicial->moneda }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-secondary small mb-1">
                            Método de pago
                        </div>

                        <div class="fw-semibold text-capitalize">
                            {{ $pagoInicial->metodo_pago }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-secondary small mb-1">
                            Fecha de pago
                        </div>

                        <div class="fw-semibold">
                            {{ $pagoInicial->fecha_pago_mostrada }}
                        </div>
                    </div>

                    <div>
                        <div class="text-secondary small mb-1">
                            Referencia
                        </div>

                        <div class="fw-semibold">
                            {{ $pagoInicial->referencia ?: '—' }}
                        </div>
                    </div>

                    @else

                    <div class="text-secondary text-center py-3">
                        <i class="fa-solid fa-receipt fa-2x mb-3"></i>

                        <p class="mb-0">
                            No hay información de pago.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVerTicket" tabindex="-1" aria-labelledby="modalVerTicketLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVerTicketLabel">
                    <i class="fa-solid fa-receipt me-2"></i>
                    Ticket
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body" id="ticketContenido"></div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>

                <button type="button" class="btn btn-primary" id="btnImprimirTicket" disabled>
                    <i class="fa-solid fa-print me-2"></i>
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalTicket = document.getElementById('modalVerTicket');
        const contenido = document.getElementById('ticketContenido');
        const btnImprimir = document.getElementById('btnImprimirTicket');
        let tarjetaActual = '';

        function avisar(tipo, mensaje) {
            if (window.showToast) {
                window.showToast(tipo, mensaje);
            } else {
                alert(mensaje);
            }
        }

        function escapar(texto) {
            const div = document.createElement('div');
            div.textContent = texto || '';
            return div.innerHTML;
        }

        // Descarga la vista del ticket y se queda solo con la tarjeta del comprobante
        function obtenerTarjeta(url) {
            return fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error(
                            response.status === 403
                                ? 'No tienes permiso para ver el ticket.'
                                : 'No se pudo cargar el ticket.'
                        );
                    }

                    return response.text();
                })
                .then(function (html) {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const tarjeta = doc.querySelector('.ticket-card');

                    if (!tarjeta) {
                        throw new Error('No se encontró el contenido del ticket.');
                    }

                    return tarjeta.outerHTML;
                });
        }

        // Imprime sin cambiar de página: iframe oculto con el estilo de la app en claro
        function imprimir(tarjetaHtml) {
            document.querySelectorAll('iframe.ticket-print-frame').forEach(function (frame) {
                frame.remove();
            });

            const iframe = document.createElement('iframe');
            iframe.className = 'ticket-print-frame';
            iframe.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
            document.body.appendChild(iframe);

            const estilos = Array.from(
                document.querySelectorAll('link[rel="stylesheet"], style')
            ).map(function (nodo) {
                return nodo.outerHTML;
            }).join('');

            const doc = iframe.contentWindow.document;

            doc.open();
            doc.write(
                '<!doctype html><html lang="es" data-bs-theme="light"><head>' +
                '<meta charset="utf-8"><title>Ticket</title>' +
                estilos +
                '<style>' +
                'html,body{background:#fff!important;color:#000!important}' +
                '.ticket-card{box-shadow:none!important;border:0!important;background:#fff!important;color:#000!important}' +
                '.text-secondary{color:#555!important}' +
                '</style></head><body class="p-3">' +
                tarjetaHtml +
                '</body></html>'
            );
            doc.close();

            const hojas = Array.from(
                doc.querySelectorAll('link[rel="stylesheet"]')
            ).map(function (hoja) {
                return new Promise(function (resolver) {
                    hoja.addEventListener('load', resolver);
                    hoja.addEventListener('error', resolver);
                });
            });

            Promise.race([
                Promise.all(hojas),
                new Promise(function (resolver) {
                    setTimeout(resolver, 800);
                })
            ]).then(function () {
                iframe.contentWindow.onafterprint = function () {
                    iframe.remove();
                };

                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            });
        }

        function abrirTicket(url) {
            tarjetaActual = '';
            btnImprimir.disabled = true;

            contenido.innerHTML =
                '<div class="text-center py-5">' +
                '<div class="spinner-border" role="status"></div>' +
                '</div>';

            bootstrap.Modal.getOrCreateInstance(modalTicket).show();

            obtenerTarjeta(url)
                .then(function (tarjetaHtml) {
                    tarjetaActual = tarjetaHtml;
                    contenido.innerHTML = tarjetaHtml;
                    btnImprimir.disabled = false;
                })
                .catch(function (error) {
                    contenido.innerHTML =
                        '<div class="alert alert-danger mb-0">' +
                        escapar(error.message) +
                        '</div>';
                });
        }

        document.addEventListener('click', function (evento) {
            const boton = evento.target.closest('.btn-ver-ticket');

            if (boton) {
                abrirTicket(boton.dataset.url || '');
            }
        });

        btnImprimir.addEventListener('click', function () {
            if (tarjetaActual) {
                imprimir(tarjetaActual);
            }
        });

        modalTicket.addEventListener('hidden.bs.modal', function () {
            tarjetaActual = '';
            contenido.innerHTML = '';
        });
    });
</script>

@if ($membresia->estado === 'activa')
    <div class="modal fade" id="modalCancelarMembresia" tabindex="-1" aria-labelledby="modalCancelarMembresiaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCancelarMembresiaLabel">
                        <i class="fa-solid fa-ban text-danger me-2"></i>
                        Cancelar membresía
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formCancelarMembresia" action="{{ route('membresias.cancelar', $membresia) }}">
                    @csrf

                    <div class="modal-body text-center">
                        <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                            style="width: 58px; height: 58px;">
                            <i class="fa-solid fa-ban fa-lg"></i>
                        </div>

                        <h6 class="fw-bold mb-2">
                            ¿Cancelar membresía?
                        </h6>

                        <p class="text-secondary mb-0">
                            Estás a punto de cancelar la membresía de
                            <strong>
                                {{ $nombreMiembro }}
                            </strong>.
                        </p>

                        <p class="text-secondary mb-0 mt-2">
                            Plan:
                            <strong>
                                {{ $membresia->plan->nombre }}
                            </strong>
                        </p>

                        <p class="text-secondary mt-3 mb-0">
                            La membresía conservará su historial y cambiará a estado cancelada.
                        </p>
                    </div>

                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            No, volver
                        </button>

                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-ban me-2"></i>
                            Cancelar membresía
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@endsection