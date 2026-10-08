@extends('layouts.app')

@section('content')

@php
    $accionesPlanes = $accionesPlanes ?? collect();
    $moneda = $moneda ?? ['codigo' => 'MXN', 'simbolo' => '$', 'tasa' => 1];

    $accionesPlanesJs = $accionesPlanes->map(function ($accion) {
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
        <h1 class="fw-bold mb-1">Planes</h1>
        <p class="text-secondary mb-0">Administración de planes del gimnasio.</p>
    </div>

    @foreach ($accionesPlanes as $accion)
        @if ($accion->slug === 'planes.crear')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoPlan"
                title="{{ $accion->nombre }}">
                {!! $accion->icono ?: '<i class="fa-solid fa-plus me-2"></i>' !!}
                {{ $accion->nombre }}
            </button>
        @endif
    @endforeach
</div>

<script>
    window.accionesPlanes = @json($accionesPlanesJs);
    window.monedaPlanes = @json($moneda);
</script>

<div class="card border-0 shadow-sm planes-card">
    <div class="card-body">
        <div class="table-responsive planes-table-wrap">
            <table id="tablaPlanes" class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Duración</th>
                        <th>Precio</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($planes as $plan)
                    <tr data-id="{{ $plan->id }}" data-nombre="{{ $plan->nombre }}"
                        data-descripcion="{{ $plan->descripcion ?? '' }}"
                        data-duracion-dias="{{ $plan->duracion_dias }}" data-precio="{{ $plan->precio }}"
                        data-activo="{{ $plan->activo ? 1 : 0 }}">
                        <td>
                            <span class="fw-semibold plan-nombre">
                                {{ $plan->nombre }}
                            </span>
                        </td>

                        <td>
                            <span class="text-secondary plan-descripcion">
                                {{ $plan->descripcion ?: 'Sin descripción' }}
                            </span>
                        </td>

                        <td>
                            <span class="plan-duracion">
                                {{ $plan->duracion_dias }} {{ $plan->duracion_dias === 1 ? 'día' : 'días' }}
                            </span>
                        </td>

                        <td>
                            <span class="fw-semibold plan-precio">
                                {{ $moneda['simbolo'] }}{{ number_format((float) $plan->precio * $moneda['tasa'], 2) }}
                            </span>
                        </td>

                        <td class="text-center">
                            <span class="plan-estado-text {{ $plan->activo ? 'activo' : 'inactivo' }}">
                                {{ $plan->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td>
                            <div class="plan-actions">
                                @foreach ($accionesPlanes as $accion)
                                @if ($accion->slug === 'planes.editar')
                                <button type="button" class="btn btn-outline-primary plan-action-btn btn-editar-plan"
                                    data-id="{{ $plan->id }}" title="{{ $accion->nombre }}"
                                    data-tooltip="{{ $accion->nombre }}">
                                    {!! $accion->icono ?: '<i class="fa-solid fa-pen-to-square"></i>' !!}
                                </button>
                                @endif

                                @if ($accion->slug === 'planes.estado')
                                <button type="button"
                                    class="plan-toggle-btn {{ $plan->activo ? 'activo' : 'inactivo' }}"
                                    data-id="{{ $plan->id }}" data-activo="{{ $plan->activo ? 1 : 0 }}"
                                    aria-pressed="{{ $plan->activo ? 'true' : 'false' }}"
                                    title="{{ $plan->activo ? 'Desactivar plan' : 'Activar plan' }}"
                                    data-tooltip="{{ $plan->activo ? 'Desactivar plan' : 'Activar plan' }}">
                                    <span class="plan-toggle-track">
                                        <span class="plan-toggle-thumb"></span>
                                    </span>
                                </button>
                                @endif

                                @if ($accion->slug === 'planes.eliminar')
                                <button type="button" class="btn btn-outline-danger plan-action-btn btn-eliminar-plan"
                                    data-id="{{ $plan->id }}" data-name="{{ $plan->nombre }}"
                                    title="{{ $accion->nombre }}" data-tooltip="{{ $accion->nombre }}">
                                    {!! $accion->icono ?: '<i class="fa-regular fa-trash-can"></i>' !!}
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

<div class="modal fade" id="modalNuevoPlan" tabindex="-1" aria-labelledby="modalNuevoPlanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="modalNuevoPlanLabel">
                    <i class="fa-solid fa-tags me-2"></i>
                    Nuevo plan
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="formNuevoPlan" action="{{ route('planes.store') }}" method="POST" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nuevoPlanNombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nuevoPlanNombre" name="nombre" maxlength="100"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="nuevoPlanDescripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="nuevoPlanDescripcion" name="descripcion" rows="3"
                            maxlength="255"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nuevoPlanDuracion" class="form-label">Duración</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="nuevoPlanDuracion" name="duracion_dias"
                                    min="1" step="1" required>
                                <span class="input-group-text">días</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="nuevoPlanPrecio" class="form-label">Precio</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $moneda['simbolo'] }} {{ $moneda['codigo'] }}</span>
                                <input type="number" class="form-control" id="nuevoPlanPrecio" name="precio" min="0"
                                    max="99999999.99" step="0.01" required>
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

<div class="modal fade" id="modalEditarPlan" tabindex="-1" aria-labelledby="modalEditarPlanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="modalEditarPlanLabel">
                    <i class="fa-solid fa-pen-to-square me-2"></i>
                    Editar plan
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="formEditarPlan" method="POST" novalidate autocomplete="off">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editarPlanNombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editarPlanNombre" name="nombre" maxlength="100"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="editarPlanDescripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="editarPlanDescripcion" name="descripcion" rows="3"
                            maxlength="255"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editarPlanDuracion" class="form-label">Duración</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="editarPlanDuracion" name="duracion_dias"
                                    min="1" step="1" required>
                                <span class="input-group-text">días</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="editarPlanPrecio" class="form-label">Precio</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ $moneda['simbolo'] }} {{ $moneda['codigo'] }}</span>
                                <input type="number" class="form-control" id="editarPlanPrecio" name="precio" min="0"
                                    max="99999999.99" step="0.01" required>
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
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEliminarPlan" tabindex="-1" aria-labelledby="modalEliminarPlanLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarPlanLabel">
                    <i class="fa-regular fa-trash-can me-2"></i>
                    Eliminar plan
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form id="formEliminarPlan" method="POST">
                @csrf
                @method('DELETE')

                <div class="modal-body text-center">

                    <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                        style="width: 58px; height: 58px;">
                        <i class="fa-solid fa-trash fa-lg"></i>
                    </div>

                    <h6 class="fw-bold mb-2">
                        ¿Eliminar plan?
                    </h6>

                    ¿Estás seguro de que deseas eliminar el plan
                        <strong id="nombrePlanEliminar"></strong>?
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-danger">
                        <i class="fa-regular fa-trash-can me-2"></i>
                        Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection