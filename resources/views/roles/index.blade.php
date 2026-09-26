@extends('layouts.app')
@section('title', 'Roles')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="fw-bold mb-1">Roles</h1>
        <p class="text-secondary mb-0">Administración de roles del sistema.</p>
    </div>

    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoRol">
        <i class="fa-solid fa-user-shield me-2"></i>
        Nuevo rol
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablaRoles" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Descripción</th>
                        <th>Fecha de registro</th>
                        <th class="text-end px-4">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($roles as $rol)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $rol->name }}</span>
                            </td>

                            <td>
                                @if ($rol->description)
                                    {{ $rol->description }}
                                @else
                                    <span class="text-secondary">Sin descripción</span>
                                @endif
                            </td>

                            <td>
                                <span class="text-secondary">
                                    {{ $rol->created_at?->format('d/m/Y H:i') }}
                                </span>
                            </td>

                            <td class="text-end px-4">
                                <div class="rol-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary rol-action-btn"
                                        title="Editar rol" data-bs-toggle="modal" data-bs-target="#modalEditarRol"
                                        data-id="{{ $rol->id }}" data-name="{{ $rol->name }}"
                                        data-description="{{ $rol->description }}"
                                        data-url="{{ route('roles.update', $rol) }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button type="button" class="btn btn-sm btn-outline-danger rol-action-btn"
                                        title="Eliminar rol" data-bs-toggle="modal"
                                        data-bs-target="#modalEliminarRol" data-id="{{ $rol->id }}"
                                        data-name="{{ $rol->name }}"
                                        data-url="{{ route('roles.destroy', $rol) }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL NUEVO ROL --}}
<div class="modal fade" id="modalNuevoRol" tabindex="-1" aria-labelledby="modalNuevoRolLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoRolLabel">
                    <i class="fa-solid fa-user-shield me-2"></i>
                    Nuevo rol
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formNuevoRol" action="{{ route('roles.store') }}" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre</label>

                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name') }}" required>

                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="form-label">Descripción</label>

                        <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                            name="description" rows="3">{{ old('description') }}</textarea>

                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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

{{-- MODAL EDITAR ROL --}}
<div class="modal fade" id="modalEditarRol" tabindex="-1" aria-labelledby="modalEditarRolLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarRolLabel">
                    <i class="fa-solid fa-user-pen me-2"></i>
                    Editar rol
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEditarRol" novalidate autocomplete="off">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <input type="hidden" id="editar_id" name="id">

                    <div class="mb-3">
                        <label for="editar_name" class="form-label">Nombre</label>

                        <input type="text" class="form-control" id="editar_name" name="name" required>
                    </div>

                    <div>
                        <label for="editar_description" class="form-label">Descripción</label>

                        <textarea class="form-control" id="editar_description" name="description" rows="3"></textarea>
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

{{-- MODAL ELIMINAR ROL --}}
<div class="modal fade" id="modalEliminarRol" tabindex="-1" aria-labelledby="modalEliminarRolLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarRolLabel">
                    <i class="fa-solid fa-trash text-danger me-2"></i>
                    Eliminar rol
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEliminarRol">
                @csrf
                @method('DELETE')

                <input type="hidden" id="eliminar_id" name="id">

                <div class="modal-body text-center">
                    <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                        style="width: 58px; height: 58px;">
                        <i class="fa-solid fa-trash fa-lg"></i>
                    </div>

                    <h6 class="fw-bold mb-2">¿Eliminar rol?</h6>
                    <p class="text-secondary mb-0">Estás a punto de eliminar el rol
                        <strong id="eliminar_nombre">
                            este rol
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