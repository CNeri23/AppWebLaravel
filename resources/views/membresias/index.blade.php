@extends('layouts.app')
@section('content')

    @php
        $accionesMembresias = $accionesMembresias ?? collect();
        $moneda = $moneda ?? ['codigo' => 'MXN', 'simbolo' => '$', 'tasa' => 1];

        $accionesMembresiasJs = $accionesMembresias->map(function ($accion) {
            return [
                'id' => $accion->id,
                'nombre' => $accion->nombre,
                'slug' => $accion->slug,
                'icono' => $accion->icono,
            ];
        })->values();

    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">Membresías</h1>
            <p class="text-secondary mb-0">Administración de membresías del gimnasio.</p>
        </div>
        @foreach ($accionesMembresias as $accion)
            @if ($accion->slug === 'membresias.crear')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaMembresia"
                    title="{{ $accion->nombre }}">
                    {!! $accion->icono ?: '<i class="fa-solid fa-id-card me-2"></i>' !!}
                    {{ $accion->nombre }}
                </button>
            @endif
        @endforeach
    </div>
    <script>
        window.accionesMembresias = @json($accionesMembresiasJs);
        window.monedaMembresias = @json($moneda);
    </script>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive membresias-table-wrap">
                <table id="tablaMembresias" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Miembro</th>
                            <th>Plan</th>
                            <th>Periodo</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th class="text-center px-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($membresias as $membresia)
                            @php
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

                                $ticketImprimirUrl = $membresia->ticket
                                    ? route('tickets.print', $membresia->ticket)
                                    : '';
                            @endphp

                            <tr data-id="{{ $membresia->id }}" data-nombre="{{ $nombreMiembro }}"
                                data-plan="{{ $membresia->plan->nombre }}" data-estado="{{ $membresia->estado }}"
                                data-precio="{{ $membresia->precio_mostrado }}"
                                data-observaciones="{{ $membresia->observaciones ?? '' }}"
                                data-fecha-inicio="{{ $membresia->fecha_inicio->format($dateFormat) }}"
                                data-fecha-fin="{{ $membresia->fecha_fin->format($dateFormat) }}"
                                data-url-show="{{ route('membresias.show', $membresia) }}"
                                data-url-update="{{ route('membresias.update', $membresia) }}"
                                data-url-renovar="{{ route('membresias.renovar', $membresia) }}"
                                data-url-cancelar="{{ route('membresias.cancelar', $membresia) }}"
                                data-url-ticket="{{ $membresia->ticket_url ?? '' }}"
                                data-url-ticket-imprimir="{{ $ticketImprimirUrl }}">

                                <td>
                                    <div class="fw-semibold">
                                        {{ $nombreMiembro }}
                                    </div>

                                    @if ($membresia->persona->email)
                                        <div class="small text-secondary">
                                            {{ $membresia->persona->email }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <span class="fw-medium">
                                        {{ $membresia->plan->nombre }}
                                    </span>
                                </td>

                                <td>
                                    <div>
                                        {{ $membresia->fecha_inicio->format($dateFormat) }}
                                        —
                                        {{ $membresia->fecha_fin->format($dateFormat) }}
                                    </div>

                                    <div class="small text-secondary">
                                        {{ $membresia->fecha_inicio->diffInDays($membresia->fecha_fin) + 1 }}
                                        días
                                    </div>
                                </td>

                                <td>
                                    <span class="fw-semibold">
                                        {{ $moneda['simbolo'] ?? '$' }}{{ number_format((float) $membresia->precio_mostrado, 2) }}
                                        {{ $moneda['codigo'] ?? 'MXN' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge rounded-pill {{ $estadoClase }}">
                                        {{ $estadoNombre }}
                                    </span>
                                </td>

                                <td class="text-end px-4">
                                    <div class="membresia-actions">
                                        @foreach ($accionesMembresias as $accion)
                                            @if ($accion->slug === 'membresias.ver')
                                                <a href="{{ route('membresias.show', $membresia) }}"
                                                    class="btn btn-sm btn-outline-primary membresia-action-btn"
                                                    data-tooltip="{{ $accion->nombre }}" title="{{ $accion->nombre }}">
                                                    {!! $accion->icono ?: '<i class="fa-solid fa-eye"></i>' !!}
                                                </a>
                                            @endif

                                            @if ($accion->slug === 'membresias.editar')
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary membresia-action-btn btn-editar-membresia"
                                                    title="{{ $accion->nombre }}" data-tooltip="{{ $accion->nombre }}"
                                                    data-id="{{ $membresia->id }}" data-name="{{ $nombreMiembro }}"
                                                    data-plan="{{ $membresia->plan->nombre }}" data-estado="{{ $membresia->estado }}"
                                                    data-observaciones="{{ $membresia->observaciones ?? '' }}"
                                                    data-fecha-inicio="{{ $membresia->fecha_inicio->format($dateFormat) }}"
                                                    data-fecha-fin="{{ $membresia->fecha_fin->format($dateFormat) }}"
                                                    data-url="{{ route('membresias.update', $membresia) }}">
                                                    {!! $accion->icono ?: '<i class="fa-solid fa-pen"></i>' !!}
                                                </button>
                                            @endif

                                            @if ($accion->slug === 'membresias.renovar' && $membresia->estado !== 'cancelada')
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-success membresia-action-btn btn-renovar-membresia"
                                                    title="{{ $accion->nombre }}" data-tooltip="{{ $accion->nombre }}"
                                                    data-id="{{ $membresia->id }}" data-name="{{ $nombreMiembro }}"
                                                    data-plan="{{ $membresia->plan->nombre }}" data-estado="{{ $membresia->estado }}"
                                                    data-fecha-fin="{{ $membresia->fecha_fin->format($dateFormat) }}"
                                                    data-url="{{ route('membresias.renovar', $membresia) }}">
                                                    {!! $accion->icono ?: '<i class="fa-solid fa-arrows-rotate"></i>' !!}
                                                </button>
                                            @endif

                                            @if ($accion->slug === 'membresias.cancelar' && $membresia->estado === 'activa')
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger membresia-action-btn btn-cancelar-membresia"
                                                    title="{{ $accion->nombre }}" data-tooltip="{{ $accion->nombre }}"
                                                    data-id="{{ $membresia->id }}" data-name="{{ $nombreMiembro }}"
                                                    data-plan="{{ $membresia->plan->nombre }}"
                                                    data-url="{{ route('membresias.cancelar', $membresia) }}">
                                                    {!! $accion->icono ?: '<i class="fa-solid fa-ban"></i>' !!}
                                                </button>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalNuevaMembresia" tabindex="-1" aria-labelledby="modalNuevaMembresiaLabel"
        aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevaMembresiaLabel"> <i class="fa-solid fa-id-card me-2"></i> Nueva
                        membresía </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formNuevaMembresia" action="{{ route('membresias.store') }}" novalidate
                    autocomplete="off">
                    @csrf

                    <div class="modal-body">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-file-signature me-2"></i>
                            Datos de la membresía
                        </h6>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="persona_id" class="form-label">
                                    Miembro
                                </label>

                                <select class="form-select" id="persona_id" name="persona_id" required>
                                    <option value="">Selecciona un miembro</option>

                                    @foreach ($miembros as $miembro)
                                                                    <option value="{{ $miembro->id }}">
                                                                        {{ trim(
                                            $miembro->nombre . ' ' .
                                            $miembro->apellido_paterno . ' ' .
                                            ($miembro->apellido_materno ?? '')
                                        ) }}
                                                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label for="plan_id" class="form-label">
                                    Plan
                                </label>

                                <select class="form-select" id="plan_id" name="plan_id" required>
                                    <option value="">Selecciona un plan</option>

                                    @foreach ($planes as $plan)
                                        <option value="{{ $plan->id }}" data-precio="{{ $plan->precio_mostrado }}"
                                            data-duracion="{{ $plan->duracion_dias }}">
                                            {{ $plan->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Precio
                                </label>

                                <div class="form-control membresia-price-display" id="precioPlan">
                                    —
                                </div>

                                <div class="form-text">
                                    El precio se toma del plan seleccionado.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="metodo_pago" class="form-label">
                                    Método de pago
                                </label>

                                <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                                    <option value="">Selecciona un método</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="referenciaContainer">
                                <label for="referencia" class="form-label">
                                    Referencia
                                </label>

                                <input type="text" class="form-control" id="referencia" name="referencia" maxlength="100">

                                <div class="form-text">
                                    Opcional. Folio, autorización o referencia del pago.
                                </div>
                            </div>

                            <div class="col-md-6 d-none" id="montoRecibidoContainer">
                                <label for="monto_recibido" class="form-label">
                                    Monto recibido
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        {{ $moneda['simbolo'] ?? '$' }}
                                    </span>

                                    <input type="number" class="form-control" id="monto_recibido" name="monto_recibido"
                                        min="0" step="0.01" inputmode="decimal">
                                </div>

                                <div class="form-text">
                                    Importe entregado por el cliente.
                                </div>
                            </div>

                            <div class="col-md-6 d-none" id="cambioContainer">
                                <label class="form-label">
                                    Cambio
                                </label>

                                <div class="form-control membresia-price-display" id="cambio">
                                    —
                                </div>

                                <div class="form-text">
                                    Se calcula automáticamente.
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="observaciones" class="form-label">
                                    Observaciones
                                </label>

                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                    maxlength="255"></textarea>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2 mt-4 mb-0">
                            <i class="fa-solid fa-circle-info mt-1"></i>

                            <div>
                                <div class="fw-semibold">
                                    Inicio de la membresía
                                </div>

                                <div>
                                    La membresía comenzará el día de la contratación y finalizará de acuerdo con la
                                    duración del plan seleccionado.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalEditarMembresia" tabindex="-1" aria-labelledby="modalEditarMembresiaLabel"
        aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarMembresiaLabel"> <i class="fa-solid fa-pen me-2"></i> Editar
                        membresía </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formEditarMembresia" novalidate autocomplete="off">
                    @csrf
                    @method('PUT')

                    <input type="hidden" id="editar_id" name="id">

                    <div class="modal-body">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-file-signature me-2"></i>
                            Datos de la membresía
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Miembro
                                </label>

                                <div class="form-control bg-body-secondary" id="editar_nombre">
                                    —
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Plan
                                </label>

                                <div class="form-control bg-body-secondary" id="editar_plan">
                                    —
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Estado
                                </label>

                                <div class="form-control bg-body-secondary" id="editar_estado">
                                    —
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Periodo
                                </label>

                                <div class="form-control bg-body-secondary" id="editar_periodo">
                                    —
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="editar_observaciones" class="form-label">
                                    Observaciones
                                </label>

                                <textarea class="form-control" id="editar_observaciones" name="observaciones" rows="4"
                                    maxlength="255"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalRenovarMembresia" tabindex="-1" aria-labelledby="modalRenovarMembresiaLabel"
        aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRenovarMembresiaLabel"> <i
                            class="fa-solid fa-arrows-rotate text-success me-2"></i> Renovar membresía </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formRenovarMembresia" novalidate autocomplete="off">
                    @csrf

                    <input type="hidden" id="renovar_id" name="id">

                    <div class="modal-body">
                        <h6 class="fw-semibold mb-3">
                            <i class="fa-solid fa-file-signature me-2"></i>
                            Datos de la renovación
                        </h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Miembro
                                </label>

                                <div class="form-control bg-body-secondary" id="renovar_nombre">
                                    —
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">
                                    Membresía actual
                                </label>

                                <div class="form-control bg-body-secondary" id="renovar_plan_actual">
                                    —
                                </div>
                            </div>

                            <div class="col-md-8">
                                <label for="renovar_plan_id" class="form-label">
                                    Nuevo plan
                                </label>

                                <select class="form-select" id="renovar_plan_id" name="plan_id" required>
                                    <option value="">Selecciona un plan</option>

                                    @foreach ($planes as $plan)
                                        <option value="{{ $plan->id }}" data-precio="{{ $plan->precio_mostrado }}"
                                            data-duracion="{{ $plan->duracion_dias }}">
                                            {{ $plan->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">
                                    Precio
                                </label>

                                <div class="form-control membresia-price-display" id="precioRenovacion">
                                    —
                                </div>

                                <div class="form-text">
                                    Precio actual del plan.
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="renovar_metodo_pago" class="form-label">
                                    Método de pago
                                </label>

                                <select class="form-select" id="renovar_metodo_pago" name="metodo_pago" required>
                                    <option value="">Selecciona un método</option>
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="renovarReferenciaContainer">
                                <label for="renovar_referencia" class="form-label">
                                    Referencia
                                </label>

                                <input type="text" class="form-control" id="renovar_referencia" name="referencia"
                                    maxlength="100">

                                <div class="form-text">
                                    Opcional. Folio, autorización o referencia del pago.
                                </div>
                            </div>

                            <div class="col-md-6 d-none" id="renovarMontoRecibidoContainer">
                                <label for="renovar_monto_recibido" class="form-label">
                                    Monto recibido
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        {{ $moneda['simbolo'] ?? '$' }}
                                    </span>

                                    <input type="number" class="form-control" id="renovar_monto_recibido"
                                        name="monto_recibido" min="0" step="0.01" inputmode="decimal">
                                </div>

                                <div class="form-text">
                                    Importe entregado por el cliente.
                                </div>
                            </div>

                            <div class="col-md-6 d-none" id="renovarCambioContainer">
                                <label class="form-label">
                                    Cambio
                                </label>

                                <div class="form-control membresia-price-display" id="renovarCambio">
                                    —
                                </div>

                                <div class="form-text">
                                    Se calcula automáticamente.
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="renovar_observaciones" class="form-label">
                                    Observaciones
                                </label>

                                <textarea class="form-control" id="renovar_observaciones" name="observaciones" rows="3"
                                    maxlength="255"></textarea>
                            </div>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2 mt-4 mb-0">
                            <i class="fa-solid fa-circle-info mt-1"></i>

                            <div>
                                <div class="fw-semibold">
                                    Periodo de renovación
                                </div>

                                <div id="renovar_periodo_info">
                                    Selecciona un plan para calcular el nuevo periodo de la membresía.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalCancelarMembresia" tabindex="-1" aria-labelledby="modalCancelarMembresiaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCancelarMembresiaLabel"> <i
                            class="fa-solid fa-ban text-danger me-2"></i> Cancelar membresía </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formCancelarMembresia">
                    @csrf

                    <input type="hidden" id="cancelar_id" name="id">

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
                            <strong id="cancelar_nombre">
                                este miembro
                            </strong>.
                        </p>

                        <p class="text-secondary mb-0 mt-2">
                            Plan:
                            <strong id="cancelar_plan">
                                —
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

@endsection