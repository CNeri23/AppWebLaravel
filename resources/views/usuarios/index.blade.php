@extends('layouts.app')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="fw-bold mb-1">Usuarios</h1>
        <p class="text-secondary mb-0">Administración de usuarios del sistema.</p>
    </div>

    @foreach ($accionesUsuarios as $accion)
        @if ($accion->slug === 'usuarios.crear')
            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                data-bs-target="#modalNuevoUsuario" title="{{ $accion->nombre }}">
                {!! $accion->icono ?: '<i class="fa-solid fa-user-plus me-2"></i>' !!}
                {{ $accion->nombre }}
            </button>
        @endif
    @endforeach
</div>

@php
    $accionesUsuariosJs = $accionesUsuarios->map(function ($accion) {
        return [
        'id' => $accion->id,
        'nombre' => $accion->nombre,
        'slug' => $accion->slug,
        'icono' => $accion->icono,
        ];
    })->values();
@endphp

<script>
    window.accionesUsuarios = @json($accionesUsuariosJs);
    window.politicaPassword = @json($politicaPassword);
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive" usuarios-table-wrap>
            <table id="tablaUsuarios" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Correo electrónico</th>
                        <th>Estado</th>
                        <th>Fecha de registro</th>
                        <th class="text-center px-4">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($usuarios as $usuario)
                    <tr>
                        <td class="fw-semibold">
                            {{ $usuario->username }}
                        </td>

                        <td>
                            {{ $usuario->persona?->nombre_completo ?: '—' }}
                        </td>

                        <td>
                            {{ $usuario->email ?: '—' }}
                        </td>

                        <td>
                            @if ($usuario->activo)
                            <span class="badge rounded-pill text-bg-success">Activo</span>
                            @else
                            <span class="badge rounded-pill text-bg-secondary">Inactivo</span>
                            @endif
                        </td>

                        <td>
                            <span class="text-secondary">
                                {{ $usuario->created_at?->format('d/m/Y H:i') }}
                            </span>
                        </td>

                        <td class="text-end px-4">
                            <div class="usuario-actions">

                                @foreach ($accionesUsuarios as $accion)

                                @if ($accion->slug === 'usuarios.editar')
                                <button type="button"
                                    class="btn btn-sm btn-outline-primary usuario-action-btn"
                                    title="{{ $accion->nombre }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEditarUsuario"
                                    data-id="{{ $usuario->id }}"
                                    data-name="{{ $usuario->name }}"
                                    data-username="{{ $usuario->username }}"
                                    data-nombre="{{ $usuario->persona?->nombre }}"
                                    data-apellido-paterno="{{ $usuario->persona?->apellido_paterno }}"
                                    data-apellido-materno="{{ $usuario->persona?->apellido_materno }}"
                                    data-telefono="{{ $usuario->persona?->telefono }}"
                                    data-email="{{ $usuario->persona?->email }}"
                                    data-activo="{{ $usuario->activo ? 1 : 0 }}"
                                    data-url="{{ route('usuarios.update', $usuario) }}">
                                    {!! $accion->icono ?: '<i class="fa-solid fa-pen"></i>' !!}
                                </button>

                                @elseif ($accion->slug === 'usuarios.toggle')
                                <button type="button"
                                    class="btn btn-sm usuario-action-btn btn-estado-usuario {{ $usuario->activo ? 'btn-outline-secondary' : 'btn-outline-success' }}"
                                    title="{{ $usuario->activo ? 'Desactivar' : 'Activar' }}"
                                    data-id="{{ $usuario->id }}"
                                    data-name="{{ $usuario->username }}"
                                    data-activo="{{ $usuario->activo ? 1 : 0 }}"
                                    data-url="{{ route('usuarios.estado', $usuario) }}">
                                    <i class="fa-solid {{ $usuario->activo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>

                                @elseif ($accion->slug === 'usuarios.password')
                                <button type="button"
                                    class="btn btn-sm btn-outline-warning usuario-action-btn"
                                    title="{{ $accion->nombre }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalPasswordUsuario"
                                    data-id="{{ $usuario->id }}"
                                    data-name="{{ $usuario->name }}"
                                    data-url="{{ route('usuarios.password', $usuario) }}">
                                    {!! $accion->icono ?: '<i class="fa-solid fa-key"></i>' !!}
                                </button>

                                @elseif ($accion->slug === 'usuarios.roles')
                                <button type="button"
                                    class="btn btn-sm btn-outline-success usuario-action-btn"
                                    title="{{ $accion->nombre }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalRolesUsuario"
                                    data-id="{{ $usuario->id }}"
                                    data-name="{{ $usuario->name }}"
                                    data-roles="{{ $usuario->roles->pluck('id')->implode(',') }}"
                                    data-url="{{ route('usuarios.roles', $usuario) }}">
                                    {!! $accion->icono ?: '<i class="fa-solid fa-user-shield"></i>' !!}
                                </button>

                                @elseif ($accion->slug === 'usuarios.eliminar')
                                <button type="button"
                                    class="btn btn-sm btn-outline-danger usuario-action-btn"
                                    title="{{ $accion->nombre }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEliminarUsuario"
                                    data-id="{{ $usuario->id }}"
                                    data-name="{{ $usuario->name }}"
                                    data-url="{{ route('usuarios.destroy', $usuario) }}">
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

{{-- MODAL NUEVO USUARIO --}}
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1" aria-labelledby="modalNuevoUsuarioLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable usuario-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoUsuarioLabel">
                    <i class="fa-solid fa-user-plus me-2"></i>
                    Nuevo usuario
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formNuevoUsuario" action="{{ route('usuarios.store') }}" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">
                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-right-to-bracket me-2"></i>Acceso</h6>

                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" maxlength="50"
                            placeholder="Ej. juan.perez" required>
                        <div class="invalid-feedback" id="username-error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Ingresa la contraseña" required autocomplete="new-password">
                        <div class="invalid-feedback" id="password-error"></div>
                        <div class="form-text" id="password-hint">{{ $politicaPassword['descripcion'] }}</div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <input type="password" class="form-control" id="password_confirmation"
                            name="password_confirmation" placeholder="Confirma la contraseña" required
                            autocomplete="new-password">
                        <div class="invalid-feedback" id="password-confirmation-error"></div>
                    </div>

                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-user me-2"></i>Datos personales</h6>

                    <div class="mb-3">
                        <label for="nuevo_nombre" class="form-label">Nombre(s)</label>
                        <input type="text" class="form-control" id="nuevo_nombre" name="nombre" maxlength="100"
                            placeholder="Ingresa el nombre" required>
                        <div class="invalid-feedback" id="nombre-error"></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="nuevo_apellido_paterno" class="form-label">Apellido paterno</label>
                            <input type="text" class="form-control" id="nuevo_apellido_paterno" name="apellido_paterno"
                                maxlength="100" placeholder="Apellido paterno" required>
                            <div class="invalid-feedback" id="apellido_paterno-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="nuevo_apellido_materno" class="form-label">Apellido materno</label>
                            <input type="text" class="form-control" id="nuevo_apellido_materno" name="apellido_materno"
                                maxlength="100" placeholder="Apellido materno (opcional)">
                            <div class="invalid-feedback" id="apellido_materno-error"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="nuevo_telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="nuevo_telefono" name="telefono" maxlength="30"
                                placeholder="Teléfono (opcional)">
                            <div class="invalid-feedback" id="telefono-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="nuevo_email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="nuevo_email" name="email" maxlength="255"
                                placeholder="example@domain.com" required>
                            <div class="invalid-feedback" id="email-error"></div>
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

{{-- MODAL EDITAR USUARIO --}}
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable usuario-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarUsuarioLabel">
                    <i class="fa-solid fa-user-pen me-2"></i>
                    Editar usuario
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEditarUsuario" novalidate autocomplete="off">
                @csrf
                @method('PUT')

                <div class="modal-body">

                    <input type="hidden" id="editar_id" name="id">

                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-right-to-bracket me-2"></i>Acceso</h6>

                    <div class="mb-3">
                        <label for="editar_username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="editar_username" name="username" maxlength="50"
                            placeholder="Ej. juan.perez" required>
                        <div class="invalid-feedback" id="editar-username-error"></div>
                    </div>

                    <h6 class="fw-semibold mb-3"><i class="fa-solid fa-user me-2"></i>Datos personales</h6>

                    <div class="mb-3">
                        <label for="editar_nombre" class="form-label">Nombre(s)</label>
                        <input type="text" class="form-control" id="editar_nombre" name="nombre" maxlength="100"
                            placeholder="Ingresa el nombre" required>
                        <div class="invalid-feedback" id="editar-nombre-error"></div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="editar_apellido_paterno" class="form-label">Apellido paterno</label>
                            <input type="text" class="form-control" id="editar_apellido_paterno" name="apellido_paterno"
                                maxlength="100" placeholder="Apellido paterno" required>
                            <div class="invalid-feedback" id="editar-apellido_paterno-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="editar_apellido_materno" class="form-label">Apellido materno</label>
                            <input type="text" class="form-control" id="editar_apellido_materno" name="apellido_materno"
                                maxlength="100" placeholder="Apellido materno (opcional)">
                            <div class="invalid-feedback" id="editar-apellido_materno-error"></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editar_telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="editar_telefono" name="telefono" maxlength="30"
                                placeholder="Teléfono (opcional)">
                            <div class="invalid-feedback" id="editar-telefono-error"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="editar_email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="editar_email" name="email" maxlength="255"
                                placeholder="example@domain.com" required>
                            <div class="invalid-feedback" id="editar-email-error"></div>
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

{{-- MODAL CAMBIAR CONTRASEÑA --}}
<div class="modal fade" id="modalPasswordUsuario" tabindex="-1" aria-labelledby="modalPasswordUsuarioLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPasswordUsuarioLabel">
                    <i class="fa-solid fa-key me-2"></i>
                    Cambiar contraseña
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formPasswordUsuario" novalidate autocomplete="off">
                @csrf
                @method('PUT')

                <div class="modal-body">

                    <input type="hidden" id="password_usuario_id" name="id">

                    <div class="alert alert-light border mb-3">
                        <div class="d-flex gap-2">
                            <i class="fa-solid fa-circle-info text-primary mt-1"></i>

                            <div>
                                <div class="fw-semibold">Cambiar contraseña</div>

                                <small class="text-secondary">
                                    Estás cambiando la contraseña del usuario
                                    <strong id="password_usuario_nombre">
                                        este usuario
                                    </strong>.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password_nueva" class="form-label">Nueva contraseña</label>

                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password_nueva" name="password" required>

                        <div class="invalid-feedback" id="password-nueva-error">@error('password'){{ $message }}@enderror</div>
                        <div class="form-text" id="password-nueva-hint">{{ $politicaPassword['descripcion'] }}</div>
                    </div>

                    <div>
                        <label for="password_nueva_confirmation" class="form-label">
                            Confirmar nueva contraseña
                        </label>

                        <input type="password" class="form-control" id="password_nueva_confirmation"
                            name="password_confirmation" required>

                        <div class="invalid-feedback" id="password-nueva-confirmation-error"></div>
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

{{-- MODAL ASIGNAR ROLES --}}
<div class="modal fade" id="modalRolesUsuario" tabindex="-1" aria-labelledby="modalRolesUsuarioLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRolesUsuarioLabel">
                    <i class="fa-solid fa-user-shield me-2"></i>
                    Asignar roles
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formRolesUsuario" novalidate>
                @csrf
                @method('PUT')

                <div class="modal-body">

                    <input type="hidden" id="roles_usuario_id" name="id">

                    <div class="mb-3">
                        <div class="fw-semibold">
                            Usuario
                        </div>

                        <div class="text-secondary" id="roles_usuario_nombre">
                            Este usuario
                        </div>
                    </div>

                    @if ($roles->isNotEmpty())
                    <div>
                        <label class="form-label fw-semibold">
                            Roles disponibles
                        </label>

                        <div class="border rounded p-3">

                            @foreach ($roles as $rol)
                            <div class="form-check">
                                <input class="form-check-input rol-usuario-checkbox" type="checkbox" name="roles[]"
                                    value="{{ $rol->id }}" id="rol_usuario_{{ $rol->id }}">

                                <label class="form-check-label" for="rol_usuario_{{ $rol->id }}">

                                    {{ $rol->name }}

                                    @if ($rol->description)
                                    <span class="text-secondary">
                                        — {{ $rol->description }}
                                    </span>
                                    @endif

                                </label>
                            </div>
                            @endforeach

                        </div>
                    </div>
                    @else
                    <div class="alert alert-light border mb-0">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        No hay roles registrados.
                    </div>
                    @endif
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

{{-- MODAL ELIMINAR USUARIO --}}
<div class="modal fade" id="modalEliminarUsuario" tabindex="-1" aria-labelledby="modalEliminarUsuarioLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarUsuarioLabel">
                    <i class="fa-solid fa-trash text-danger me-2"></i>
                    Eliminar usuario
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEliminarUsuario">
                @csrf
                @method('DELETE')

                <input type="hidden" id="eliminar_id" name="id">

                <div class="modal-body text-center">

                    <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                        style="width: 58px; height: 58px;">
                        <i class="fa-solid fa-trash fa-lg"></i>
                    </div>

                    <h6 class="fw-bold mb-2">¿Eliminar usuario?</h6>

                    <p class="text-secondary mb-0">
                        Estás a punto de eliminar a
                        <strong id="eliminar_nombre">
                            este usuario
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