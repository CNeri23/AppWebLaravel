@extends('layouts.app')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="fw-bold mb-1">Cajas</h1>
        <p class="text-secondary mb-0">Administración de cajas registradoras del sistema.</p>
    </div>

    @foreach ($accionesCajas as $accion)
        @if ($accion->slug === 'cajas.crear')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                data-bs-target="#modalNuevaCaja" title="{{ $accion->nombre }}">
                {!! $accion->icono ?: '<i class="fa-solid fa-plus me-2"></i>' !!}
                {{ $accion->nombre }}
            </button>
        @endif
    @endforeach
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
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive cajas-table-wrap">
            <table id="tablaCajas" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Sesión actual</th>
                        <th>Usuario</th>
                        <th class="text-end px-4">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($cajas as $caja)
                    @php
                        $sesionAbierta = $caja->sesiones->first();
                    @endphp

                    <tr>
                        <td>
                            <div class="fw-semibold">
                                {{ $caja->nombre }}
                            </div>
                        </td>

                        <td>
                            <span class="text-secondary">
                                {{ $caja->descripcion ?: 'Sin descripción' }}
                            </span>
                        </td>

                        <td>
                            @if ($caja->activo)
                                <span class="badge text-bg-success">
                                    Activa
                                </span>
                            @else
                                <span class="badge text-bg-secondary">
                                    Inactiva
                                </span>
                            @endif
                        </td>

                        <td>
                            @if ($sesionAbierta)
                                <span class="badge text-bg-primary">
                                    Abierta
                                </span>
                            @else
                                <span class="text-secondary">
                                    Sin sesión
                                </span>
                            @endif
                        </td>

                        <td>
                            @if ($sesionAbierta && $sesionAbierta->usuarioApertura)
                                {{ $sesionAbierta->usuarioApertura->name }}
                            @else
                                <span class="text-secondary">
                                    —
                                </span>
                            @endif
                        </td>

                        <td class="text-end px-4">
                            <div class="caja-actions">

                                @foreach ($accionesCajas as $accion)

                                    @if ($accion->slug === 'cajas.editar')
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary caja-action-btn"
                                            title="{{ $accion->nombre }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditarCaja"
                                            data-id="{{ $caja->id }}"
                                            data-nombre="{{ $caja->nombre }}"
                                            data-descripcion="{{ $caja->descripcion }}"
                                            data-url="{{ route('cajas.update', $caja) }}">
                                            {!! $accion->icono ?: '<i class="fa-solid fa-pen"></i>' !!}
                                        </button>

                                    @elseif ($accion->slug === 'cajas.ver')
                                        <a href="{{ route('cajas.sesiones.index', ['caja_id' => $caja->id]) }}"
                                            class="btn btn-sm btn-outline-info caja-action-btn"
                                            title="{{ $accion->nombre }}">
                                            {!! $accion->icono ?: '<i class="fa-regular fa-eye"></i>' !!}
                                        </a>

                                    @endif

                                @endforeach

                                @foreach ($accionesCajas as $accion)
                                    @if ($accion->slug === 'cajas.editar')
                                        <button type="button"
                                            class="usuario-toggle-btn caja-toggle-btn {{ $caja->activo ? 'activo' : 'inactivo' }}"
                                            title="{{ $caja->activo ? 'Desactivar caja' : 'Activar caja' }}"
                                            data-tooltip="{{ $caja->activo ? 'Desactivar caja' : 'Activar caja' }}"
                                            aria-label="{{ $caja->activo ? 'Desactivar caja' : 'Activar caja' }}"
                                            aria-pressed="{{ $caja->activo ? 'true' : 'false' }}"
                                            data-id="{{ $caja->id }}"
                                            data-name="{{ $caja->nombre }}"
                                            data-activo="{{ $caja->activo ? 1 : 0 }}"
                                            data-url="{{ route('cajas.estado', $caja) }}">
                                            <span class="usuario-toggle-track">
                                                <span class="usuario-toggle-thumb"></span>
                                            </span>
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

{{-- MODAL NUEVA CAJA --}}
<div class="modal fade" id="modalNuevaCaja" tabindex="-1" aria-labelledby="modalNuevaCajaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaCajaLabel">
                    <i class="fa-solid fa-cash-register me-2"></i>
                    Nueva caja
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formNuevaCaja" action="{{ route('cajas.store') }}" novalidate
                autocomplete="off">
                @csrf

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">
                            Nombre
                        </label>

                        <input type="text" class="form-control" id="nombre" name="nombre" maxlength="100" placeholder="Nombre de la caja" required>
                        <div class="invalid-feedback" id="nombre-error"></div>
                    </div>

                    <div>
                        <label for="descripcion" class="form-label">
                            Descripción
                        </label>

                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="255" placeholder="Descripción de la caja"></textarea>
                        <div class="invalid-feedback" id="descripcion-error"></div>
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

{{-- MODAL EDITAR CAJA --}}
<div class="modal fade" id="modalEditarCaja" tabindex="-1" aria-labelledby="modalEditarCajaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarCajaLabel">
                    <i class="fa-solid fa-pen-to-square me-2"></i>
                    Editar caja
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEditarCaja" novalidate autocomplete="off">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <input type="hidden" id="editar_caja_id" name="id">

                    <div class="mb-3">
                        <label for="editar_nombre" class="form-label">
                            Nombre
                        </label>

                        <input type="text" class="form-control" id="editar_nombre" name="nombre" maxlength="100" required>
                        <div class="invalid-feedback" id="editar_nombre-error"></div>
                    </div>

                    <div>
                        <label for="editar_descripcion" class="form-label">
                            Descripción
                        </label>

                        <textarea class="form-control" id="editar_descripcion" name="descripcion" rows="3" maxlength="255"></textarea>
                        <div class="invalid-feedback" id="editar_descripcion-error"></div>
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

{{-- MODAL ACTIVAR / DESACTIVAR CAJA --}}
<div class="modal fade" id="modalEstadoCaja" tabindex="-1" aria-labelledby="modalEstadoCajaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="modalEstadoCajaLabel">
                    <i class="fa-solid fa-toggle-on me-2"></i>
                    Cambiar estado
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEstadoCaja" novalidate>
                @csrf
                @method('PATCH')

                <input type="hidden" id="estado_caja_id" name="id">

                <div class="modal-body text-center">
                    <div id="estadoCajaIcono" class="mx-auto mb-3 rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                        <i class="fa-solid fa-toggle-on fa-lg"></i>
                    </div>

                    <h6 class="fw-bold mb-2" id="estadoCajaTitulo">
                        ¿Cambiar estado de la caja?
                    </h6>

                    <p class="text-secondary mb-0">
                        La caja
                        <strong id="estado_caja_nombre"> esta caja </strong>
                        cambiará de estado.
                    </p>

                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-primary" id="btnEstadoCaja">
                        <i class="fa-solid fa-floppy-disk me-2"></i>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection