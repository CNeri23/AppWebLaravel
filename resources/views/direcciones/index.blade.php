@extends('layouts.app')
@section('content')

@php
    $accionesDirecciones = $accionesDirecciones ?? collect();

    $accionesDireccionesJs = $accionesDirecciones->map(function ($accion) {
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
        <h1 class="fw-bold mb-1">Direcciones</h1>
        <p class="text-secondary mb-0">Administración de direcciones registradas.</p>
    </div>

    @foreach ($accionesDirecciones as $accion)
        @if ($accion->slug === 'direcciones.crear')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaDireccion"
                title="{{ $accion->nombre }}">
                {!! $accion->icono ?: '<i class="fa-solid fa-location-dot me-2"></i>' !!}
                {{ $accion->nombre }}
            </button>
        @endif
    @endforeach
</div>

<script>
    window.accionesDirecciones = @json($accionesDireccionesJs);
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive direcciones-table-wrap">
            <table id="tablaDirecciones" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Calle</th>
                        <th>No. Ext</th>
                        <th>No. Int</th>
                        <th>Colonia</th>
                        <th>C.P.</th>
                        <th>Municipio</th>
                        <th>Estado</th>
                        <th>País</th>
                        <th class="text-center">Personas</th>
                        <th class="text-center px-4">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($direcciones as $direccion)
                    <tr>
                        <td>
                            {{ $direccion->calle }}
                        </td>
                        <td>
                            {{ $direccion->numero_exterior }}
                        </td>
                        <td>
                            {{ $direccion->numero_interior ?: '—' }}
                        </td>
                        <td>
                            {{ $direccion->colonia }}
                        </td>
                        <td>
                            {{ $direccion->codigo_postal }}
                        </td>
                        <td>
                            {{ $direccion->municipio }}
                        </td>
                        <td>
                            {{ $direccion->estado }}
                        </td>
                        <td>
                            {{ $direccion->pais }}
                        </td>
                        <td class="text-center">
                            @if ($direccion->personas_count > 0)
                            <span class="badge text-bg-primary">
                                {{ $direccion->personas_count }}
                            </span>
                            @else
                            <span class="badge text-bg-secondary">
                                0
                            </span>
                            @endif
                        </td>

                        <td class="text-end px-4">
                            <div class="direccion-actions">
                                @foreach ($accionesDirecciones as $accion)
                                @if ($accion->slug === 'direcciones.editar')
                                <button type="button" class="btn btn-sm btn-outline-primary direccion-action-btn"
                                    title="{{ $accion->nombre }}" data-bs-toggle="modal"
                                    data-bs-target="#modalEditarDireccion" data-id="{{ $direccion->id }}"
                                    data-calle="{{ $direccion->calle }}"
                                    data-numero-exterior="{{ $direccion->numero_exterior }}"
                                    data-numero-interior="{{ $direccion->numero_interior }}"
                                    data-colonia="{{ $direccion->colonia }}"
                                    data-codigo-postal="{{ $direccion->codigo_postal }}"
                                    data-municipio="{{ $direccion->municipio }}" data-estado="{{ $direccion->estado }}"
                                    data-pais="{{ $direccion->pais }}"
                                    data-personas-count="{{ $direccion->personas_count }}"
                                    data-url="{{ route('direcciones.update', $direccion) }}">
                                    {!! $accion->icono ?: '<i class="fa-solid fa-pen"></i>' !!}
                                </button>

                                @elseif ($accion->slug === 'direcciones.eliminar')
                                <button type="button" class="btn btn-sm btn-outline-danger direccion-action-btn"
                                    title="{{ $direccion->personas_count > 0 ? 'Dirección asignada' : $accion->nombre }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEliminarDireccion"
                                    data-id="{{ $direccion->id }}" data-name="
                                {{ trim(
                                    $direccion->calle . ' ' .
                                    $direccion->numero_exterior .
                                    ($direccion->numero_interior
                                        ? ' Int. ' . $direccion->numero_interior
                                        : '') .
                                    ', ' .
                                    $direccion->colonia
                                ) }}" data-personas-count="{{ $direccion->personas_count }}"
                                    data-url="{{ route('direcciones.destroy', $direccion) }}"
                                    @if ($direccion->personas_count > 0)
                                    disabled
                                    @endif>
                                    {!! $accion->icono ?: '<i class="fa-solid fa-trash"></i>' !!}
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

{{-- MODAL NUEVA DIRECCIÓN --}}
<div class="modal fade" id="modalNuevaDireccion" tabindex="-1" aria-labelledby="modalNuevaDireccionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaDireccionLabel">
                    <i class="fa-solid fa-location-dot me-2"></i>
                    Nueva dirección
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formNuevaDireccion" action="{{ route('direcciones.store') }}" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-8">
                            <label for="calle" class="form-label">
                                Calle
                            </label>

                            <input type="text" class="form-control" id="calle" name="calle" maxlength="150" placeholder="Nombre de la calle" required>
                        </div>

                        <div class="col-md-4">
                            <label for="numero_exterior" class="form-label">
                                Número exterior
                            </label>

                            <input type="text" class="form-control" id="numero_exterior" name="numero_exterior" maxlength="20" placeholder="Ej: 12" required>
                        </div>

                        <div class="col-md-4">
                            <label for="numero_interior" class="form-label">
                                Número interior
                            </label>

                            <input type="text" class="form-control" id="numero_interior" name="numero_interior" maxlength="20" placeholder="Ej: 3A">
                        </div>

                        <div class="col-md-8">
                            <label for="colonia" class="form-label">
                                Colonia
                            </label>

                            <input type="text" class="form-control" id="colonia" name="colonia" maxlength="100" placeholder="Nombre de la colonia" required>
                        </div>

                        <div class="col-md-4">
                            <label for="codigo_postal" class="form-label">
                                Código postal
                            </label>

                            <input type="text" class="form-control" id="codigo_postal" name="codigo_postal" maxlength="5" inputmode="numeric" placeholder="Ej: 12345" required>
                        </div>

                        <div class="col-md-8">
                            <label for="municipio" class="form-label">
                                Municipio
                            </label>

                            <input type="text" class="form-control" id="municipio" name="municipio" maxlength="100" placeholder="Nombre del municipio" required>
                        </div>

                        <div class="col-md-6">
                            <label for="estado" class="form-label">
                                Estado
                            </label>

                            <input type="text" class="form-control" id="estado" name="estado" maxlength="100" placeholder="Nombre del estado" required>
                        </div>

                        <div class="col-md-6">
                            <label for="pais" class="form-label">
                                País
                            </label>

                            <input type="text" class="form-control" id="pais" name="pais" maxlength="100" value="México" required>
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

{{-- MODAL EDITAR DIRECCIÓN --}}
<div class="modal fade" id="modalEditarDireccion" tabindex="-1" aria-labelledby="modalEditarDireccionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarDireccionLabel">
                    <i class="fa-solid fa-location-dot me-2"></i>
                    Editar dirección
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEditarDireccion" novalidate autocomplete="off">
                @csrf
                @method('PUT')

                <div class="modal-body">

                    <input type="hidden" id="editar_id" name="id">


                    <div class="row g-3">

                        <div class="col-md-8">
                            <label for="editar_calle" class="form-label">
                                Calle
                            </label>

                            <input type="text" class="form-control" id="editar_calle" name="calle" maxlength="150" required>
                        </div>

                        <div class="col-md-4">
                            <label for="editar_numero_exterior" class="form-label">
                                Número exterior
                            </label>

                            <input type="text" class="form-control" id="editar_numero_exterior" name="numero_exterior" maxlength="20" required>
                        </div>

                        <div class="col-md-4">
                            <label for="editar_numero_interior" class="form-label">
                                Número interior
                            </label>

                            <input type="text" class="form-control" id="editar_numero_interior" name="numero_interior" placeholder="Ej: 3A" maxlength="20">
                        </div>

                        <div class="col-md-8">
                            <label for="editar_colonia" class="form-label">
                                Colonia
                            </label>

                            <input type="text" class="form-control" id="editar_colonia" name="colonia" maxlength="100" required>
                        </div>

                        <div class="col-md-4">
                            <label for="editar_codigo_postal" class="form-label">
                                Código postal
                            </label>

                            <input type="text" class="form-control" id="editar_codigo_postal" name="codigo_postal" maxlength="5" inputmode="numeric" required>
                        </div>

                        <div class="col-md-8">
                            <label for="editar_municipio" class="form-label">
                                Municipio
                            </label>

                            <input type="text" class="form-control" id="editar_municipio" name="municipio" maxlength="100" required>
                        </div>

                        <div class="col-md-6">
                            <label for="editar_estado" class="form-label">
                                Estado
                            </label>

                            <input type="text" class="form-control" id="editar_estado" name="estado" maxlength="100" required>
                        </div>

                        <div class="col-md-6">
                            <label for="editar_pais" class="form-label">
                                País
                            </label>

                            <input type="text" class="form-control" id="editar_pais" name="pais" maxlength="100" required>
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

{{-- MODAL ELIMINAR DIRECCIÓN --}}

<div class="modal fade" id="modalEliminarDireccion" tabindex="-1" aria-labelledby="modalEliminarDireccionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarDireccionLabel">
                    <i class="fa-solid fa-trash text-danger me-2"></i>
                    Eliminar dirección
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEliminarDireccion">
                @csrf
                @method('DELETE')
                <input type="hidden" id="eliminar_id" name="id">

                <div class="modal-body text-center">
                    <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                        <i class="fa-solid fa-trash fa-lg"></i>
                    </div>

                    <h6 class="fw-bold mb-2">
                        ¿Eliminar dirección?
                    </h6>

                    <p class="text-secondary mb-0">
                        Estás a punto de eliminar la dirección
                        <strong id="eliminar_nombre">
                            esta dirección
                        </strong>.
                        Esta acción no se puede deshacer.
                    </p>
                </div>

                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-trash me-2"></i>
                        Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection