@extends('layouts.app')
@section('content')

    <script>
        window.politicaPassword = @json($politicaPassword);
    </script>

    <div class="profile-header-card card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row align-items-center gap-4">

                <div class="profile-avatar-wrapper">
                    @if ($usuario->profile_image)
                        <img src="{{ asset('storage/' . $usuario->profile_image) }}" alt="Foto de {{ $usuario->name }}"
                            class="profile-avatar" id="profileAvatar">
                    @else
                        <div class="profile-avatar profile-avatar-placeholder" id="profileAvatar">
                            {{ strtoupper(mb_substr($usuario->name, 0, 1)) }}
                        </div>
                    @endif

                    <button type="button" class="profile-avatar-edit" data-bs-toggle="modal"
                        data-bs-target="#modalFotoPerfil" title="Cambiar foto">
                        <i class="fa-solid fa-camera"></i>
                    </button>
                </div>

                <div class="grow flex-grow-1 text-center text-md-start">
                    <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-2 mb-1">
                        <h2 class="fw-bold mb-0" id="profileName">
                            {{ $usuario->name }}
                        </h2>

                        <span class="text-secondary align-self-md-end" id="profileUsername">
                            &#64;{{ $usuario->username }}
                        </span>

                        <span class="badge text-bg-success profile-status-badge">
                            <i class="fa-solid fa-circle me-1"></i>
                            Activa
                        </span>
                    </div>

                    <p class="text-secondary mb-2" id="profileEmail">
                        <i class="fa-solid fa-envelope me-1"></i>
                        {{ $usuario->email }}
                    </p>

                    <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
                        @forelse ($usuario->roles as $rol)
                            <span class="badge bg-primary-subtle text-primary">
                                <i class="fa-solid fa-shield-halved me-1"></i>
                                {{ $rol->name }}
                            </span>
                        @empty
                            <span class="badge bg-secondary-subtle text-secondary">
                                Sin rol asignado
                            </span>
                        @endforelse

                        <span class="text-secondary small d-flex align-items-center">
                            <i class="fa-regular fa-calendar me-1"></i>
                            Miembro desde {{ $usuario->created_at?->timezone($zonaHoraria)->format($formatoFecha) }}
                        </span>
                    </div>
                </div>

                <div class="profile-actions">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#modalEditarPerfil">
                        <i class="fa-solid fa-user-pen me-2"></i>
                        Editar perfil
                    </button>

                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                        data-bs-target="#modalCambiarPassword">
                        <i class="fa-solid fa-key me-2"></i>
                        Cambiar contraseña
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="profile-section-icon bg-warning-subtle text-warning">
                                <i class="fa-solid fa-shield-halved"></i>
                            </span>

                            <div>
                                <h5 class="fw-bold mb-0">
                                    Seguridad
                                </h5>

                                <small class="text-secondary">
                                    Protege el acceso a tu cuenta.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body px-4">
                    <div class="profile-security-item">
                        <div class="profile-security-icon bg-primary-subtle text-primary">
                            <i class="fa-solid fa-envelope-circle-check"></i>
                        </div>

                        <div class="grow">
                            <div class="fw-semibold">
                                Correo electrónico
                            </div>

                            <small class="text-secondary" id="profileInfoEmail">
                                {{ $usuario->email }}
                            </small>
                        </div>

                        <span class="badge text-bg-success">
                            Verificado
                        </span>
                    </div>

                    <div class="profile-security-item border-0 pb-0">
                        <div class="profile-security-icon bg-secondary-subtle text-secondary">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>

                        <div class="grow">
                            <div class="fw-semibold">
                                Última actualización
                            </div>

                            <small class="text-secondary">
                                Cambios más recientes en tu cuenta.
                            </small>
                        </div>

                        <small class="text-secondary" data-profile-updated="{{ $usuario->updated_at?->toIso8601String() }}"
                            title="{{ $usuario->updated_at?->timezone($zonaHoraria)->format($formatoFecha . ' ' . $formatoHora) }}">
                            {{ $usuario->updated_at?->diffForHumans() }}
                        </small>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="profile-section-icon bg-success-subtle text-success">
                            <i class="fa-solid fa-user-shield"></i>
                        </span>

                        <div>
                            <h5 class="fw-bold mb-0">
                                Rol y permisos
                            </h5>

                            <small class="text-secondary">
                                Accesos asignados a tu cuenta.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card-body px-4">

                    @forelse ($usuario->roles as $rol)
                        <div class="profile-role-card">
                            <div class="profile-role-icon">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>

                            <div class="grow">
                                <div class="fw-semibold">
                                    {{ $rol->name }}
                                </div>

                                <small class="text-secondary">
                                    {{ $rol->description ?: 'Rol asignado a tu cuenta.' }}
                                </small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fa-solid fa-shield-halved fs-2 text-secondary mb-2"></i>

                            <p class="text-secondary mb-0">
                                No tienes un rol asignado.
                            </p>
                        </div>
                    @endforelse

                    <div class="profile-permission-summary mt-3">
                        <div>
                            <span class="profile-info-label">
                                Permisos asignados
                            </span>

                            <span class="profile-info-value">
                                {{ $usuario->permissions()->count() }}
                            </span>
                        </div>
                        <i class="fa-solid fa-key text-secondary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL EDITAR PERFIL --}}
    <div class="modal fade" id="modalEditarPerfil" tabindex="-1" aria-labelledby="modalEditarPerfilLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarPerfilLabel">
                        <i class="fa-solid fa-user-pen me-2"></i>
                        Editar perfil
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formEditarPerfil" action="{{ route('perfil.update') }}" novalidate autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="perfil_username" class="form-label">Usuario</label>

                            <input type="text" class="form-control" id="perfil_username" name="username"
                                value="{{ $usuario->username }}" maxlength="50" required autocomplete="off">

                            <div class="form-text">Es el que usas para iniciar sesión.</div>
                            <div class="invalid-feedback d-block" id="perfil-username-error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="perfil_nombre" class="form-label">Nombre(s)</label>

                            <input type="text" class="form-control" id="perfil_nombre" name="nombre"
                                value="{{ $usuario->persona?->nombre }}" maxlength="100" required>

                            <div class="invalid-feedback d-block" id="perfil-nombre-error"></div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="perfil_apellido_paterno" class="form-label">Apellido paterno</label>

                                <input type="text" class="form-control" id="perfil_apellido_paterno"
                                    name="apellido_paterno" value="{{ $usuario->persona?->apellido_paterno }}"
                                    maxlength="100" required>

                                <div class="invalid-feedback d-block" id="perfil-apellido-paterno-error"></div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="perfil_apellido_materno" class="form-label">Apellido materno</label>

                                <input type="text" class="form-control" id="perfil_apellido_materno"
                                    name="apellido_materno" value="{{ $usuario->persona?->apellido_materno }}"
                                    maxlength="100">

                                <div class="invalid-feedback d-block" id="perfil-apellido-materno-error"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="perfil_telefono" class="form-label">Teléfono</label>

                            <input type="tel" class="form-control" id="perfil_telefono" name="telefono"
                                value="{{ $usuario->persona?->telefono }}" maxlength="30">

                            <div class="invalid-feedback d-block" id="perfil-telefono-error"></div>
                        </div>

                        <div>
                            <label for="perfil_email" class="form-label">Correo electrónico</label>

                            <input type="email" class="form-control" id="perfil_email" name="email"
                                value="{{ $usuario->persona?->email }}" maxlength="255" required>

                            <div class="invalid-feedback d-block" id="perfil-email-error"></div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary" id="btnGuardarPerfil">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL CAMBIAR CONTRASEÑA --}}
    <div class="modal fade" id="modalCambiarPassword" tabindex="-1" aria-labelledby="modalCambiarPasswordLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCambiarPasswordLabel">
                        <i class="fa-solid fa-key me-2"></i>
                        Cambiar contraseña
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formCambiarPassword" action="{{ route('perfil.password') }}" novalidate autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                Contraseña actual
                            </label>

                            <div class="input-group password-input-group">
                                <input type="password" class="form-control" id="current_password" name="current_password"
                                    autocomplete="current-password" required>

                                <button type="button" class="input-group-text toggle-password"
                                    data-password-target="current_password" tabindex="-1" aria-label="Mostrar contraseña">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>

                            <div class="invalid-feedback d-block" id="current-password-error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="profile_password" class="form-label">
                                Nueva contraseña
                            </label>

                            <div class="input-group password-input-group">
                                <input type="password" class="form-control" id="profile_password" name="password"
                                    autocomplete="new-password" minlength="{{ $politicaPassword['min'] }}" required>

                                <button type="button" class="input-group-text toggle-password"
                                    data-password-target="profile_password" tabindex="-1" aria-label="Mostrar contraseña">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>

                            <div class="form-text" id="profile-password-hint">
                                {{ $politicaPassword['descripcion'] }}
                            </div>

                            <div class="invalid-feedback d-block" id="profile-password-error"></div>
                        </div>

                        <div>
                            <label for="profile_password_confirmation" class="form-label">
                                Confirmar nueva contraseña
                            </label>

                            <div class="input-group password-input-group">
                                <input type="password" class="form-control" id="profile_password_confirmation"
                                    name="password_confirmation" autocomplete="new-password" required>

                                <button type="button" class="input-group-text toggle-password"
                                    data-password-target="profile_password_confirmation" tabindex="-1"
                                    aria-label="Mostrar contraseña">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>

                            <div class="invalid-feedback d-block" id="profile-password-confirmation-error"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary" id="btnCambiarPassword">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL FOTO DE PERFIL --}}
    <div class="modal fade" id="modalFotoPerfil" tabindex="-1" aria-labelledby="modalFotoPerfilLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFotoPerfilLabel">
                        <i class="fa-solid fa-camera me-2"></i>
                        Foto de perfil
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formFotoPerfil" action="{{ route('perfil.foto') }}" enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="modal-body">
                        <div class="profile-photo-preview mb-4">
                            @if ($usuario->profile_image)
                                <img src="{{ asset('storage/' . $usuario->profile_image) }}" alt="Foto de perfil"
                                    id="photoPreview">
                            @else
                                <div class="profile-photo-preview-placeholder" id="photoPreview">
                                    {{ strtoupper(mb_substr($usuario->name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label for="profile_image" class="form-label">
                                Seleccionar imagen
                            </label>

                            <input type="file" class="form-control" id="profile_image" name="profile_image"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>

                            <div class="form-text">
                                JPG, JPEG, PNG o WEBP. Máximo 2 MB.
                            </div>

                            <div class="invalid-feedback d-block" id="profile-image-error"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        @if ($usuario->profile_image)
                            <button type="button" class="btn btn-outline-danger me-auto" id="btnEliminarFoto"
                                data-url="{{ route('perfil.foto.delete') }}">
                                <i class="fa-solid fa-trash me-2"></i>
                                Eliminar foto
                            </button>
                        @endif

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-primary" id="btnGuardarFoto">
                            <i class="fa-solid fa-floppy-disk me-2"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection