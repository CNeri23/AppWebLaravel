@extends('layouts.app')
@section('title', 'Categorías financieras')

@section('content')
@php
    $accionesCategorias = $accionesCategorias ?? collect();
    $accionesCategoriasJs = $accionesCategorias->map(fn ($accion) => [
        'id' => $accion->id,
        'nombre' => $accion->nombre,
        'slug' => $accion->slug,
        'icono' => $accion->icono,
    ])->values();
@endphp

<div class="card border-0 shadow-sm finanzas-header-card mb-4">
    <div class="card-body finanzas-header-content">
        <div class="finanzas-header-text">
            <h2 class="fw-bold mb-1">Categorías financieras</h2>
            <p class="text-secondary mb-0">Organiza tus ingresos y gastos por categoría.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <select id="filtroAmbitoCategorias" class="form-select finanzas-select" aria-label="Ámbito de las categorías">
                <option value="personal" {{ $ambito === 'personal' ? 'selected' : '' }}>Finanzas personales</option>
                <option value="gimnasio" {{ $ambito === 'gimnasio' ? 'selected' : '' }}>Finanzas del gimnasio</option>
            </select>
            @if ($accionesCategorias->contains('slug', 'finanzas.categorias.crear'))
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoriaNueva">
                    <i class="fa-solid fa-plus me-2"></i>Nueva categoría
                </button>
            @endif
        </div>
    </div>
</div>

<script>
    window.accionesCategoriasFinanzas = @json($accionesCategoriasJs);
    window.ambitoCategoriasFinanzas = @json($ambito);
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablaCategoriasFinancieras" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th class="text-center">Movimientos</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($categorias as $categoria)
                    <tr data-id="{{ $categoria->id }}" data-nombre="{{ $categoria->nombre }}"
                        data-tipo="{{ $categoria->tipo }}" data-descripcion="{{ $categoria->descripcion ?? '' }}"
                        data-movimientos="{{ $categoria->movimientos_count }}">
                        <td class="fw-semibold">{{ $categoria->nombre }}</td>
                        <td><span class="badge rounded-pill {{ $categoria->tipo === 'ingreso' ? 'text-bg-success' : 'text-bg-danger' }}">{{ $categoria->tipo === 'ingreso' ? 'Ingreso' : 'Gasto' }}</span></td>
                        <td class="text-secondary">{{ $categoria->descripcion ?: 'Sin descripción' }}</td>
                        <td class="text-center">{{ $categoria->movimientos_count }}</td>
                        <td class="text-end px-4">
                            <div class="finanzas-categoria-actions">
                                @if ($accionesCategorias->contains('slug', 'finanzas.categorias.editar'))
                                    <button type="button" class="btn btn-sm btn-outline-primary finanzas-action-btn btn-editar-categoria" data-id="{{ $categoria->id }}" data-tooltip="Editar categoría" aria-label="Editar categoría">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                @endif
                                @if ($accionesCategorias->contains('slug', 'finanzas.categorias.eliminar'))
                                    <button type="button" class="btn btn-sm btn-outline-danger finanzas-action-btn btn-eliminar-categoria" data-id="{{ $categoria->id }}" data-name="{{ $categoria->nombre }}" data-tooltip="Eliminar categoría" aria-label="Eliminar categoría" {{ $categoria->movimientos_count > 0 ? 'disabled' : '' }}>
                                        <i class="fa-regular fa-trash-can"></i>
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

<div class="modal fade" id="modalCategoriaNueva" tabindex="-1" aria-labelledby="modalCategoriaNuevaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="modalCategoriaNuevaLabel"><i class="fa-solid fa-tags me-2"></i>Nueva categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCategoriaNueva" action="{{ route('finanzas.categorias.store') }}" method="POST" novalidate autocomplete="off">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="ambito" value="{{ $ambito }}">
                    <div class="mb-3">
                        <label for="nuevaCategoriaNombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nuevaCategoriaNombre" name="nombre" maxlength="100" placeholder="Ej. Alimentación, renta, ventas..." required>
                        <div class="invalid-feedback">Escribe un nombre de hasta 100 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label for="nuevaCategoriaTipo" class="form-label">Tipo</label>
                        <select class="form-select finanzas-select" id="nuevaCategoriaTipo" name="tipo" required>
                            <option value="">Selecciona un tipo</option>
                            <option value="ingreso">Ingreso</option>
                            <option value="gasto">Gasto</option>
                        </select>
                        <div class="invalid-feedback">Selecciona ingreso o gasto.</div>
                    </div>
                    <div>
                        <label for="nuevaCategoriaDescripcion" class="form-label">Descripción <span class="text-secondary">(opcional)</span></label>
                        <textarea class="form-control" id="nuevaCategoriaDescripcion" name="descripcion" rows="3" maxlength="255" placeholder="Añade una breve descripción..."></textarea>
                        <div class="invalid-feedback">La descripción no puede superar 255 caracteres.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCategoriaEditar" tabindex="-1" aria-labelledby="modalCategoriaEditarLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="modalCategoriaEditarLabel"><i class="fa-solid fa-pen-to-square me-2"></i>Editar categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formCategoriaEditar" method="POST" novalidate autocomplete="off">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editarCategoriaNombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editarCategoriaNombre" name="nombre" maxlength="100" required>
                        <div class="invalid-feedback">Escribe un nombre de hasta 100 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label for="editarCategoriaTipo" class="form-label">Tipo</label>
                        <select class="form-select finanzas-select" id="editarCategoriaTipo" name="tipo" required>
                            <option value="ingreso">Ingreso</option>
                            <option value="gasto">Gasto</option>
                        </select>
                        <div class="invalid-feedback">Selecciona ingreso o gasto.</div>
                    </div>
                    <div>
                        <label for="editarCategoriaDescripcion" class="form-label">Descripción <span class="text-secondary">(opcional)</span></label>
                        <textarea class="form-control" id="editarCategoriaDescripcion" name="descripcion" rows="3" maxlength="255"></textarea>
                        <div class="invalid-feedback">La descripción no puede superar 255 caracteres.</div>
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
@endsection
