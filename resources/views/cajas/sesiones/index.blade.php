@extends('layouts.app')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="fw-bold mb-1">Sesiones de caja</h1>

        <p class="text-secondary mb-0">
            @if ($cajaFiltro)
                Sesiones de la caja <strong>{{ $cajaFiltro->nombre }}</strong>.
                <a href="{{ route('cajas.sesiones.index') }}" class="ms-1">Ver todas</a>
            @else
                Historial de aperturas y cierres de todas las cajas.
            @endif
        </p>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('cajas.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>
            Cajas
        </a>

        @foreach ($accionesCajas as $accion)
            @if ($accion->slug === 'cajas.abrir')
                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                    data-bs-target="#modalAbrirSesion" title="{{ $accion->nombre }}">
                    {!! $accion->icono ?: '<i class="fa-solid fa-lock-open me-2"></i>' !!}
                    {{ $accion->nombre }}
                </button>
            @endif
        @endforeach
    </div>
</div>

@php
    $accionesCajasJs = $accionesCajas->map(function ($accion) {
        return [
            'id' => $accion->id,
            'nombre' => $accion->nombre,
            'slug' => $accion->slug,
            'icono' => $accion->icono,
        ];
    })->values();
@endphp

<script>
    window.accionesCajas = @json($accionesCajasJs);
    window.codigoMonedaSesiones = @json($codigoMoneda);
    window.cajaFiltroId = @json($cajaFiltro ? $cajaFiltro->id : null);
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive sesiones-table-wrap">
            <table id="tablaSesiones" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Caja</th>
                        <th>Usuario</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th>Fondo inicial</th>
                        <th>Estado</th>
                        <th class="text-end px-4">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($sesiones as $sesion)
                    <tr>
                        <td>
                            <div class="fw-semibold">
                                {{ $sesion['caja_nombre'] }}
                            </div>
                        </td>

                        <td>
                            @if ($sesion['usuario_apertura_nombre'])
                                {{ $sesion['usuario_apertura_nombre'] }}
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>

                        <td data-order="{{ $sesion['fecha_apertura_orden'] }}">
                            <span class="text-secondary">
                                {{ $sesion['fecha_apertura_formateada'] }}
                            </span>
                        </td>

                        <td>
                            @if ($sesion['fecha_cierre_formateada'])
                                <span class="text-secondary">
                                    {{ $sesion['fecha_cierre_formateada'] }}
                                </span>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>

                        <td>
                            {{ number_format((float) $sesion['fondo_inicial_mostrado'], 2, '.', ',') }}
                            {{ $codigoMoneda }}
                        </td>

                        <td>
                            @if ($sesion['estado'] === 'abierta')
                                <span class="badge text-bg-primary">Abierta</span>
                            @else
                                <span class="badge text-bg-secondary">Cerrada</span>
                            @endif
                        </td>

                        <td class="text-end px-4">
                            <div class="sesion-actions">
                                @foreach ($accionesCajas as $accion)
                                    @if ($accion->slug === 'cajas.ver')
                                        <button type="button"
                                            class="btn btn-sm btn-outline-info sesion-action-btn"
                                            title="{{ $accion->nombre }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalDetalleSesion"
                                            data-id="{{ $sesion['id'] }}"
                                            data-url="{{ route('cajas.sesiones.show', ['sesion' => $sesion['id']]) }}">
                                            {!! $accion->icono ?: '<i class="fa-regular fa-eye"></i>' !!}
                                        </button>
                                    @endif
                                @endforeach

                                @if ($sesion['estado'] === 'abierta')
                                    @foreach ($accionesCajas as $accion)
                                        @if ($accion->slug === 'cajas.retirar')
                                            <button type="button"
                                                class="btn btn-sm btn-outline-warning sesion-action-btn"
                                                title="{{ $accion->nombre }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalRetiroSesion"
                                                data-id="{{ $sesion['id'] }}"
                                                data-caja="{{ $sesion['caja_nombre'] }}"
                                                data-disponible="{{ $sesion['efectivo_disponible_mostrado'] }}"
                                                data-url="{{ route('cajas.sesiones.retirar', ['sesion' => $sesion['id']]) }}">
                                                {!! $accion->icono ?: '<i class="fa-solid fa-hand-holding-dollar"></i>' !!}
                                            </button>
                                        @endif
                                    @endforeach

                                    @foreach ($accionesCajas as $accion)
                                        @if ($accion->slug === 'cajas.cerrar')
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger sesion-action-btn"
                                                title="{{ $accion->nombre }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalCerrarSesion"
                                                data-id="{{ $sesion['id'] }}"
                                                data-caja="{{ $sesion['caja_nombre'] }}"
                                                data-url="{{ route('cajas.sesiones.cerrar', ['sesion' => $sesion['id']]) }}"
                                                data-arqueo-url="{{ route('cajas.sesiones.arqueo', ['sesion' => $sesion['id']]) }}">
                                                {!! $accion->icono ?: '<i class="fa-solid fa-lock"></i>' !!}
                                            </button>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL ABRIR CAJA --}}
<div class="modal fade" id="modalAbrirSesion" tabindex="-1" aria-labelledby="modalAbrirSesionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAbrirSesionLabel">
                    <i class="fa-solid fa-lock-open me-2"></i>
                    Abrir caja
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formAbrirSesion" action="{{ route('cajas.sesiones.abrir') }}" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="abrir_caja_id" class="form-label">
                            Caja
                        </label>

                        <select class="form-select" id="abrir_caja_id" name="caja_id" required>
                            <option value="">Selecciona una caja</option>
                            @foreach ($cajas as $caja)
                                <option value="{{ $caja->id }}"
                                    @selected($cajaFiltro && $cajaFiltro->id === $caja->id)>
                                    {{ $caja->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="abrir_fondo_inicial" class="form-label">
                            Fondo inicial ({{ $codigoMoneda }})
                        </label>

                        <input type="number" class="form-control" id="abrir_fondo_inicial" name="fondo_inicial" min="0" step="0.01" value="0" placeholder="0.00" required>
                    </div>

                    <div>
                        <label for="abrir_observaciones" class="form-label">
                            Observaciones
                        </label>

                        <textarea class="form-control" id="abrir_observaciones" name="observaciones_apertura" rows="3" placeholder="Observaciones de la apertura de la caja" maxlength="255"></textarea>
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

{{-- MODAL DETALLE DE SESIÓN --}}
<div class="modal fade" id="modalDetalleSesion" tabindex="-1" aria-labelledby="modalDetalleSesionLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleSesionLabel">
                    <i class="fa-solid fa-cash-register me-2"></i>
                    Detalle de la sesión
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body">
                <div id="detalleSesionCargando" class="text-center text-secondary py-5">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Cargando...
                </div>

                <div id="detalleSesionContenido" class="d-none">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="text-secondary small">Caja</div>
                            <div class="fw-semibold" id="det_caja"></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-secondary small">Estado</div>
                            <div id="det_estado"></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-secondary small">Fondo inicial</div>
                            <div class="fw-semibold" id="det_fondo"></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-secondary small">Abierta por</div>
                            <div id="det_usuario_apertura"></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-secondary small">Fecha de apertura</div>
                            <div id="det_apertura"></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-secondary small">Observaciones de apertura</div>
                            <div id="det_obs_apertura"></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-secondary small">Cerrada por</div>
                            <div id="det_usuario_cierre"></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-secondary small">Fecha de cierre</div>
                            <div id="det_cierre"></div>
                        </div>

                        <div class="col-md-4">
                            <div class="text-secondary small">Autorizó el cierre</div>
                            <div id="det_autorizo"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="sesion-resumen-item">
                                <div class="text-secondary small">Entradas</div>
                                <div class="fw-semibold" id="det_entradas"></div>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="sesion-resumen-item">
                                <div class="text-secondary small">Salidas</div>
                                <div class="fw-semibold" id="det_salidas"></div>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="sesion-resumen-item">
                                <div class="text-secondary small" id="det_esperado_etiqueta">Efectivo esperado</div>
                                <div class="fw-semibold" id="det_esperado"></div>
                            </div>
                        </div>

                        <div class="col-6 col-md-3">
                            <div class="sesion-resumen-item">
                                <div class="text-secondary small">Contado / Diferencia</div>
                                <div class="fw-semibold" id="det_contado"></div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">Movimientos</h6>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Tipo</th>
                                    <th>Usuario</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>

                            <tbody id="det_movimientos"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- MODAL RETIRO DE DINERO --}}
<div class="modal fade" id="modalRetiroSesion" tabindex="-1" aria-labelledby="modalRetiroSesionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRetiroSesionLabel">
                    <i class="fa-solid fa-hand-holding-dollar me-2"></i>
                    Retiro de dinero
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formRetiroSesion" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">
                    <p class="text-secondary">
                        Salida de efectivo de la caja
                        <strong id="retiro_caja_nombre"></strong>.
                    </p>

                    <div class="mb-3">
                        <label for="retiro_monto" class="form-label">
                            Monto ({{ $codigoMoneda }})
                        </label>

                        <input type="number" class="form-control" id="retiro_monto" name="monto" min="0.01" step="0.01" placeholder="0.00" required>
                        <div class="form-text" id="retiro_disponible"></div>
                    </div>

                    <div class="mb-3">
                        <label for="retiro_concepto" class="form-label">
                            Concepto
                        </label>

                        <select class="form-select" id="retiro_concepto" name="concepto" required>
                            <option value="">Selecciona un concepto</option>
                            <option value="retiro">Retiro de efectivo</option>
                            <option value="gasto">Gasto</option>
                            <option value="compra">Compra</option>
                            <option value="ajuste">Ajuste</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="retiro_referencia" class="form-label">
                            Referencia
                        </label>

                        <input type="text" class="form-control" id="retiro_referencia" name="referencia" maxlength="100" placeholder="Folio, ticket, etc. (opcional)">
                    </div>

                    <div>
                        <label for="retiro_observaciones" class="form-label">
                            Observaciones
                        </label>

                        <textarea class="form-control" id="retiro_observaciones" name="observaciones" rows="2" maxlength="255" placeholder="Observaciones (opcional)"></textarea>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary" id="btnRetiroSesion">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL CERRAR CAJA --}}
<div class="modal fade" id="modalCerrarSesion" tabindex="-1" aria-labelledby="modalCerrarSesionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCerrarSesionLabel">
                    <i class="fa-solid fa-lock me-2"></i>
                    Cerrar caja
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formCerrarSesion" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">
                    <p class="text-secondary">
                        Vas a cerrar la caja
                        <strong id="cerrar_caja_nombre"></strong>.
                    </p>

                    <div id="cerrarArqueoCargando" class="text-center text-secondary py-3">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Calculando arqueo...
                    </div>

                    <div id="cerrarArqueoContenido" class="d-none">
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <div class="sesion-resumen-item">
                                    <div class="text-secondary small">Fondo inicial</div>
                                    <div class="fw-semibold" id="cerrar_fondo"></div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="sesion-resumen-item">
                                    <div class="text-secondary small">Entradas</div>
                                    <div class="fw-semibold" id="cerrar_entradas"></div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="sesion-resumen-item">
                                    <div class="text-secondary small">Salidas</div>
                                    <div class="fw-semibold" id="cerrar_salidas"></div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="sesion-resumen-item">
                                    <div class="text-secondary small">Efectivo esperado</div>
                                    <div class="fw-semibold" id="cerrar_esperado"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="cerrar_efectivo_contado" class="form-label">
                                Efectivo contado ({{ $codigoMoneda }})
                            </label>

                            <input type="number" class="form-control"id="cerrar_efectivo_contado" name="efectivo_contado" min="0" step="0.01" placeholder="0.00" required>
                            <div class="small mt-1" id="cerrar_diferencia"></div>
                        </div>

                        <hr>

                        <p class="small text-secondary mb-3">
                            El cierre debe ser autorizado por un administrador distinto a ti.
                        </p>

                        @if ($autorizadores->isEmpty())
                            <div class="alert alert-warning mb-3">
                                No hay administradores con permiso para autorizar cierres de caja.
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="cerrar_autorizador" class="form-label">
                                Administrador que autoriza
                            </label>

                            <select class="form-select" id="cerrar_autorizador" name="usuario_autorizacion_id" required>
                                <option value="">Selecciona un administrador</option>

                                @foreach ($autorizadores as $autorizador)
                                    <option value="{{ $autorizador->id }}">
                                        {{ $autorizador->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cerrar_password" class="form-label">
                                Contraseña del administrador
                            </label>

                            <input type="password" class="form-control" id="cerrar_password" name="password_autorizacion" autocomplete="new-password" placeholder="Ingresa la contraseña" required>
                        </div>

                        <div>
                            <label for="cerrar_observaciones" class="form-label">
                                Observaciones
                            </label>

                            <textarea class="form-control" id="cerrar_observaciones" name="observaciones_cierre" rows="2" maxlength="255" placeholder="Observaciones del cierre"></textarea>
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary" id="btnCerrarSesion">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection