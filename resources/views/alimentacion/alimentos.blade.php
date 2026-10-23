@extends('layouts.app')

@section('content')
@php
    $accionesAlimentos = $accionesAlimentos ?? collect();
    $puedeCrear = $accionesAlimentos->contains('slug', 'alimentos.crear');
    $puedeEditar = $accionesAlimentos->contains('slug', 'alimentos.editar');
    $puedeEliminar = $accionesAlimentos->contains('slug', 'alimentos.eliminar');
    $accionCrearAlimento = $accionesAlimentos->firstWhere('slug', 'alimentos.crear');
    $accionEditarAlimento = $accionesAlimentos->firstWhere('slug', 'alimentos.editar');
    $accionEliminarAlimento = $accionesAlimentos->firstWhere('slug', 'alimentos.eliminar');
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2 class="fw-bold mb-1">Catálogo de alimentos</h2>
            <p class="text-secondary mb-0">Administra los alimentos y sus valores nutricionales por cada 100 g o 100 ml.</p>
        </div>
        @if ($puedeCrear)
            <button type="button" class="btn btn-primary usuarios-header-action" data-bs-toggle="modal" data-bs-target="#modalCrearAlimento">
                {!! $accionCrearAlimento->icono ?: '<i class="fa-solid fa-plus me-2"></i>' !!}
                {{ $accionCrearAlimento->nombre }}
            </button>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablaAlimentos" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th class="ps-4">Alimento</th>
                        <th>Categoría</th>
                        <th>Unidad / porción</th>
                        <th>Calorías</th>
                        <th>Proteínas</th>
                        <th>Carbohidratos</th>
                        <th>Grasas</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($alimentos as $alimento)<tr data-id="{{ $alimento->id }}">
                        <td class="ps-4"><span class="fw-semibold">{{ $alimento->nombre }}</span></td>
                        <td>{{ $alimento->categoria ?: '—' }}</td>
                        <td>
                            <span>{{ strtoupper($alimento->unidad_base) }}</span>
                            @if ($alimento->nombre_porcion)
                                <small class="d-block text-secondary">{{ $alimento->nombre_porcion }} = {{ $alimento->cantidad_porcion }} {{ $alimento->unidad_base }}</small>
                            @endif
                        </td>
                        <td>{{ number_format((float) $alimento->calorias, 1) }} kcal</td>
                        <td>{{ number_format((float) $alimento->proteinas, 1) }} g</td>
                        <td>{{ number_format((float) $alimento->carbohidratos, 1) }} g</td>
                        <td>{{ number_format((float) $alimento->grasas, 1) }} g</td>
                        <td class="text-center">
                            <span class="badge rounded-pill {{ $alimento->activo ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $alimento->activo ? 'Activo' : 'Inactivo' }}</span>
                        </td>
                        <td class="text-center px-4">
                            <div class="usuario-actions">
                                @if ($puedeEditar)
                                <button type="button" class="btn btn-sm btn-outline-primary usuario-action-btn" data-tooltip="{{ $accionEditarAlimento->nombre }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEditarAlimento"
                                    data-id="{{ $alimento->id }}" data-nombre="{{ $alimento->nombre }}"
                                    data-categoria="{{ $alimento->categoria }}" data-unidad="{{ $alimento->unidad_base }}"
                                    data-nombre-porcion="{{ $alimento->nombre_porcion }}"
                                    data-cantidad-porcion="{{ $alimento->cantidad_porcion }}"
                                    data-calorias="{{ $alimento->calorias }}" data-proteinas="{{ $alimento->proteinas }}"
                                    data-carbohidratos="{{ $alimento->carbohidratos }}" data-grasas="{{ $alimento->grasas }}"
                                    data-activo="{{ $alimento->activo ? 1 : 0 }}"
                                    data-url="{{ route('alimentacion.alimentos.update', $alimento) }}">
                                    {!! $accionEditarAlimento->icono ?: '<i class="fa-solid fa-pen"></i>' !!}
                                </button>
                                @endif
                                @if ($puedeEliminar)
                                <button type="button" class="btn btn-sm btn-outline-danger usuario-action-btn" data-tooltip="{{ $accionEliminarAlimento->nombre }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEliminarAlimento"
                                    data-nombre="{{ $alimento->nombre }}" data-url="{{ route('alimentacion.alimentos.destroy', $alimento) }}">
                                    {!! $accionEliminarAlimento->icono ?: '<i class="fa-solid fa-trash"></i>' !!}
                                </button>
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

@if ($puedeCrear)
<div class="modal fade" id="modalCrearAlimento" tabindex="-1" aria-labelledby="modalCrearAlimentoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="modalCrearAlimentoLabel"><i class="fa-solid fa-carrot me-2"></i>Nuevo alimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" id="formCrearAlimento" action="{{ route('alimentacion.alimentos.store') }}" class="js-toast-form" novalidate autocomplete="off">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="nuevo_nombre">Nombre</label><input id="nuevo_nombre" name="nombre" class="form-control" maxlength="150" required placeholder="Ej. Pechuga de pollo"><div class="invalid-feedback" data-error-for="nombre"></div></div>
                        <div class="col-md-6"><label class="form-label" for="nuevo_categoria">Categoría</label><input id="nuevo_categoria" name="categoria" class="form-control" maxlength="80" placeholder="Proteína, fruta, cereal..."><div class="invalid-feedback" data-error-for="categoria"></div></div>
                        <div class="col-md-4"><label class="form-label" for="nuevo_unidad">Unidad base</label><select id="nuevo_unidad" name="unidad_base" class="form-select" required><option value="g">Gramos (g)</option><option value="ml">Mililitros (ml)</option></select><div class="invalid-feedback" data-error-for="unidad_base"></div></div>
                        <div class="col-md-4"><label class="form-label" for="nuevo_nombre_porcion">Nombre de porción</label><input id="nuevo_nombre_porcion" name="nombre_porcion" class="form-control" maxlength="80" placeholder="Ej. 1 pieza"><div class="invalid-feedback" data-error-for="nombre_porcion"></div></div>
                        <div class="col-md-4"><label class="form-label" for="nuevo_cantidad_porcion">Cantidad por porción</label><input id="nuevo_cantidad_porcion" name="cantidad_porcion" type="number" min="0.01" step="0.01" class="form-control" placeholder="Ej. 120"><div class="invalid-feedback" data-error-for="cantidad_porcion"></div></div>
                        <div class="col-md-3"><label class="form-label" for="nuevo_calorias">Calorías (kcal)</label><input id="nuevo_calorias" name="calorias" type="number" min="0" step="0.01" class="form-control" value="0" required><div class="invalid-feedback" data-error-for="calorias"></div></div>
                        <div class="col-md-3"><label class="form-label" for="nuevo_proteinas">Proteínas (g)</label><input id="nuevo_proteinas" name="proteinas" type="number" min="0" step="0.01" class="form-control" value="0" required><div class="invalid-feedback" data-error-for="proteinas"></div></div>
                        <div class="col-md-3"><label class="form-label" for="nuevo_carbohidratos">Carbohidratos (g)</label><input id="nuevo_carbohidratos" name="carbohidratos" type="number" min="0" step="0.01" class="form-control" value="0" required><div class="invalid-feedback" data-error-for="carbohidratos"></div></div>
                        <div class="col-md-3"><label class="form-label" for="nuevo_grasas">Grasas (g)</label><input id="nuevo_grasas" name="grasas" type="number" min="0" step="0.01" class="form-control" value="0" required><div class="invalid-feedback" data-error-for="grasas"></div></div>
                    </div>
                    <p class="form-text mt-3 mb-0">Los valores nutricionales se capturan por cada 100 g o 100 ml.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar alimento</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if ($puedeEditar)
<div class="modal fade" id="modalEditarAlimento" tabindex="-1" aria-labelledby="modalEditarAlimentoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="modalEditarAlimentoLabel"><i class="fa-solid fa-pen-to-square me-2"></i>Editar alimento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" id="formEditarAlimento" class="js-toast-form" novalidate autocomplete="off">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="editar_nombre">Nombre</label><input id="editar_nombre" name="nombre" class="form-control" maxlength="150" required><div class="invalid-feedback" data-error-for="nombre"></div></div>
                        <div class="col-md-6"><label class="form-label" for="editar_categoria">Categoría</label><input id="editar_categoria" name="categoria" class="form-control" maxlength="80"><div class="invalid-feedback" data-error-for="categoria"></div></div>
                        <div class="col-md-4"><label class="form-label" for="editar_unidad">Unidad base</label><select id="editar_unidad" name="unidad_base" class="form-select" required><option value="g">Gramos (g)</option><option value="ml">Mililitros (ml)</option></select><div class="invalid-feedback" data-error-for="unidad_base"></div></div>
                        <div class="col-md-4"><label class="form-label" for="editar_nombre_porcion">Nombre de porción</label><input id="editar_nombre_porcion" name="nombre_porcion" class="form-control" maxlength="80"><div class="invalid-feedback" data-error-for="nombre_porcion"></div></div>
                        <div class="col-md-4"><label class="form-label" for="editar_cantidad_porcion">Cantidad por porción</label><input id="editar_cantidad_porcion" name="cantidad_porcion" type="number" min="0.01" step="0.01" class="form-control"><div class="invalid-feedback" data-error-for="cantidad_porcion"></div></div>
                        <div class="col-md-3"><label class="form-label" for="editar_calorias">Calorías (kcal)</label><input id="editar_calorias" name="calorias" type="number" min="0" step="0.01" class="form-control" required><div class="invalid-feedback" data-error-for="calorias"></div></div>
                        <div class="col-md-3"><label class="form-label" for="editar_proteinas">Proteínas (g)</label><input id="editar_proteinas" name="proteinas" type="number" min="0" step="0.01" class="form-control" required><div class="invalid-feedback" data-error-for="proteinas"></div></div>
                        <div class="col-md-3"><label class="form-label" for="editar_carbohidratos">Carbohidratos (g)</label><input id="editar_carbohidratos" name="carbohidratos" type="number" min="0" step="0.01" class="form-control" required><div class="invalid-feedback" data-error-for="carbohidratos"></div></div>
                        <div class="col-md-3"><label class="form-label" for="editar_grasas">Grasas (g)</label><input id="editar_grasas" name="grasas" type="number" min="0" step="0.01" class="form-control" required><div class="invalid-feedback" data-error-for="grasas"></div></div>
                        <div class="col-md-4"><label class="form-label" for="editar_activo">Estado</label><select id="editar_activo" name="activo" class="form-select"><option value="1">Activo</option><option value="0">Inactivo</option></select><div class="invalid-feedback" data-error-for="activo"></div></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if ($puedeEliminar)
<div class="modal fade" id="modalEliminarAlimento" tabindex="-1" aria-labelledby="modalEliminarAlimentoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-semibold" id="modalEliminarAlimentoLabel"><i class="fa-solid fa-trash text-danger me-2"></i>Eliminar alimento</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <form method="POST" id="formEliminarAlimento" class="js-toast-form">
                @csrf @method('DELETE')
                <div class="modal-body text-center">
                    <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width:58px;height:58px"><i class="fa-solid fa-trash fa-lg"></i></div>
                    <p class="mb-0">¿Quieres eliminar o desactivar el alimento <strong id="eliminarAlimentoNombre">seleccionado</strong>?</p>
                    <p class="text-secondary small mt-2 mb-0">Si tiene registros históricos, se desactivará para conservar el historial nutricional.</p>
                </div>
                <div class="modal-footer justify-content-center"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-2"></i>Confirmar</button></div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editarModal = document.getElementById('modalEditarAlimento');
    editarModal?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const form = document.getElementById('formEditarAlimento');
        form.action = button.dataset.url;
        const campos = {
            nombre: 'nombre', categoria: 'categoria', unidad: 'unidad',
            nombre_porcion: 'nombrePorcion', cantidad_porcion: 'cantidadPorcion',
            calorias: 'calorias', proteinas: 'proteinas',
            carbohidratos: 'carbohidratos', grasas: 'grasas', activo: 'activo'
        };
        Object.entries(campos).forEach(([id, data]) => {
            const input = document.getElementById('editar_' + id);
            if (input) input.value = button.dataset[data] ?? '';
        });
    });
    const eliminarModal = document.getElementById('modalEliminarAlimento');
    eliminarModal?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('formEliminarAlimento').action = button.dataset.url;
        document.getElementById('eliminarAlimentoNombre').textContent = button.dataset.nombre;
    });
    @if (session('success'))
        if (typeof window.showToast === 'function') window.showToast('success', @json(session('success')));
    @endif
    @if ($errors->any())
        if (typeof window.showToast === 'function') window.showToast('error', @json($errors->first()));
    @endif
});
</script>
@endpush