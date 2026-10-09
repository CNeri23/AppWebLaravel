@extends('layouts.app')
@section('content')

@php
$accionesMiembros = $accionesMiembros ?? collect();

$accionesMiembrosJs = $accionesMiembros->map(function ($accion) {
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
        <h1 class="fw-bold mb-1">Miembros</h1>
        <p class="text-secondary mb-0">Administración de miembros del gimnasio.</p>
    </div>

    @foreach ($accionesMiembros as $accion)
    @if ($accion->slug === 'miembros.crear')
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoMiembro"
        title="{{ $accion->nombre }}">
        {!! $accion->icono ?: '<i class="fa-solid fa-user-plus me-2"></i>' !!}
        {{ $accion->nombre }}
    </button>
    @endif
    @endforeach
</div>

<script>
window.accionesMiembros = @json($accionesMiembrosJs);
window.politicaPassword = @json($politicaPassword ?? []);
</script>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive miembros-table-wrap">
            <table id="tablaMiembros" class="table table-hover align-middle mb-0 w-100">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo electrónico</th>
                        <th>Dirección</th>
                        <th class="text-center px-4">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($miembros as $miembro)
                    <tr>
                        <td>
                            {{ trim(
                                $miembro->nombre . ' ' .
                                $miembro->apellido_paterno . ' ' .
                                ($miembro->apellido_materno ?? '')
                            ) }}
                        </td>

                        <td>
                            {{ $miembro->telefono ?: '—' }}
                        </td>

                        <td>
                            {{ $miembro->email ?: '—' }}
                        </td>

                        <td>
                            @if ($miembro->direccion)
                            <span>
                                {{ $miembro->direccion->calle }}
                                {{ $miembro->direccion->numero_exterior }}

                                @if ($miembro->direccion->numero_interior)
                                Int. {{ $miembro->direccion->numero_interior }}
                                @endif

                                <span class="text-secondary">
                                    — {{ $miembro->direccion->colonia }},
                                    {{ $miembro->direccion->codigo_postal }}
                                </span>
                            </span>
                            @else
                            <span class="text-secondary">
                                Sin dirección
                            </span>
                            @endif
                        </td>

                        <td class="text-end px-4">
                            <div class="miembro-actions">

                                @foreach ($accionesMiembros as $accion)

                                @if ($accion->slug === 'miembros.editar')
                                <button type="button" class="btn btn-sm btn-outline-primary miembro-action-btn"
                                    title="{{ $accion->nombre }}" data-bs-toggle="modal"
                                    data-bs-target="#modalEditarMiembro" data-id="{{ $miembro->id }}"
                                    data-nombre="{{ $miembro->nombre }}"
                                    data-apellido-paterno="{{ $miembro->apellido_paterno }}"
                                    data-apellido-materno="{{ $miembro->apellido_materno }}"
                                    data-telefono="{{ $miembro->telefono }}" data-email="{{ $miembro->email }}"
                                    data-direccion-id="{{ $miembro->direccion_id }}"
                                    data-url="{{ route('miembros.update', $miembro) }}">
                                    {!! $accion->icono ?: '<i class="fa-solid fa-pen"></i>' !!}
                                </button>

                                @elseif ($accion->slug === 'miembros.usuario')
                                @if ($miembro->usuario_id)
                                <button type="button" class="btn btn-sm btn-success miembro-action-btn miembro-usuario-btn"
                                    title="Ya tiene usuario" data-id="{{ $miembro->id }}" data-tiene-usuario="1" disabled>
                                    <i class="fa-solid fa-user-check"></i>
                                </button>
                                @else
                                <button type="button" class="btn btn-sm btn-outline-success miembro-action-btn miembro-usuario-btn"
                                    title="{{ $accion->nombre }}" data-bs-toggle="modal"
                                    data-bs-target="#modalUsuarioMiembro" data-id="{{ $miembro->id }}"
                                    data-name="{{ trim($miembro->nombre . ' ' . $miembro->apellido_paterno . ' ' . ($miembro->apellido_materno ?? '')) }}"
                                    data-url="{{ route('miembros.usuario', $miembro) }}">
                                    <i class="fa-solid fa-user-lock"></i>
                                </button>
                                @endif

                                @elseif ($accion->slug === 'miembros.eliminar')
                                <button type="button" class="btn btn-sm btn-outline-danger miembro-action-btn"
                                    title="{{ $accion->nombre }}" data-bs-toggle="modal" data-bs-target="#modalEliminarMiembro" data-id="{{ $miembro->id }}" 
                                    data-name="
                                    {{ trim(
                                        $miembro->nombre . ' ' .
                                        $miembro->apellido_paterno . ' ' .
                                        ($miembro->apellido_materno ?? '')
                                    ) }}"
                                    data-url="{{ route('miembros.destroy', $miembro) }}">
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

{{-- MODAL NUEVO --}}
<div class="modal fade" id="modalNuevoMiembro" tabindex="-1" aria-labelledby="modalNuevoMiembroLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevoMiembroLabel">
                    <i class="fa-solid fa-user-plus me-2"></i>
                    Nuevo miembro
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formNuevoMiembro" action="{{ route('miembros.store') }}" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="nombre" class="form-label">
                                Nombre
                            </label>

                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ingresa el nombre" required>
                        </div>

                        <div class="col-md-4">
                            <label for="apellido_paterno" class="form-label">
                                Apellido paterno
                            </label>

                            <input type="text" class="form-control" id="apellido_paterno" name="apellido_paterno" placeholder="Ingresa el apellido paterno" required>
                        </div>

                        <div class="col-md-4">
                            <label for="apellido_materno" class="form-label">
                                Apellido materno
                            </label>

                            <input type="text" class="form-control" id="apellido_materno" name="apellido_materno" placeholder="Ingresa el apellido materno">
                        </div>

                        <div class="col-md-6">
                            <label for="telefono" class="form-label">
                                Teléfono
                            </label>

                            <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Ej. 5551234567">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">
                                Correo electrónico
                            </label>

                            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com">
                        </div>

                        <div class="col-12">
                            <label for="direccion_id" class="form-label">
                                Dirección
                            </label>

                            <select class="form-select" id="direccion_id" name="direccion_id" required>
                                <option value="">Selecciona una dirección</option>

                                @foreach ($direcciones as $direccion)
                                <option value="{{ $direccion->id }}">
                                    {{ $direccion->calle }}
                                    {{ $direccion->numero_exterior }}

                                    @if ($direccion->numero_interior)
                                    Int. {{ $direccion->numero_interior }}
                                    @endif

                                    — {{ $direccion->colonia }},
                                    {{ $direccion->codigo_postal }},
                                    {{ $direccion->municipio }},
                                    {{ $direccion->estado }}
                                </option>
                                @endforeach
                            </select>
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

{{-- MODAL EDITAR --}}
<div class="modal fade" id="modalEditarMiembro" tabindex="-1" aria-labelledby="modalEditarMiembroLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarMiembroLabel">
                    <i class="fa-solid fa-user-pen me-2"></i>
                    Editar miembro
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEditarMiembro" novalidate autocomplete="off">
                @csrf
                @method('PUT')

                <div class="modal-body">
                    <input type="hidden" id="editar_id" name="id">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="editar_nombre" class="form-label">
                                Nombre
                            </label>

                            <input type="text" class="form-control" id="editar_nombre" name="nombre" required>
                        </div>

                        <div class="col-md-4">
                            <label for="editar_apellido_paterno" class="form-label">
                                Apellido paterno
                            </label>

                            <input type="text" class="form-control" id="editar_apellido_paterno" name="apellido_paterno"required>
                        </div>

                        <div class="col-md-4">
                            <label for="editar_apellido_materno" class="form-label">
                                Apellido materno
                            </label>

                            <input type="text" class="form-control" id="editar_apellido_materno" name="apellido_materno">
                        </div>

                        <div class="col-md-6">
                            <label for="editar_telefono" class="form-label">
                                Teléfono
                            </label>

                            <input type="text" class="form-control" id="editar_telefono" name="telefono">
                        </div>

                        <div class="col-md-6">
                            <label for="editar_email" class="form-label">
                                Correo electrónico
                            </label>

                            <input type="email" class="form-control" id="editar_email" name="email">
                        </div>

                        <div class="col-12">
                            <label for="editar_direccion_id" class="form-label">
                                Dirección
                            </label>

                            <select class="form-select" id="editar_direccion_id" name="direccion_id" required>
                                <option value="">Selecciona una dirección</option>

                                @foreach ($direcciones as $direccion)
                                <option value="{{ $direccion->id }}">
                                    {{ $direccion->calle }}
                                    {{ $direccion->numero_exterior }}

                                    @if ($direccion->numero_interior)
                                    Int. {{ $direccion->numero_interior }}
                                    @endif

                                    — {{ $direccion->colonia }},
                                    {{ $direccion->codigo_postal }},
                                    {{ $direccion->municipio }},
                                    {{ $direccion->estado }}
                                </option>
                                @endforeach
                            </select>
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

{{-- MODAL ELIMINAR --}}
<div class="modal fade" id="modalEliminarMiembro" tabindex="-1" aria-labelledby="modalEliminarMiembroLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEliminarMiembroLabel">
                    <i class="fa-solid fa-trash text-danger me-2"></i>
                    Eliminar miembro
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formEliminarMiembro">
                @csrf
                @method('DELETE')

                <input type="hidden" id="eliminar_id" name="id">

                <div class="modal-body text-center">
                    <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                        style="width: 58px; height: 58px;">
                        <i class="fa-solid fa-trash fa-lg"></i>
                    </div>

                    <h6 class="fw-bold mb-2">
                        ¿Eliminar miembro?
                    </h6>

                    <p class="text-secondary mb-0">
                        Estás a punto de eliminar a
                        <strong id="eliminar_nombre">
                            este miembro
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

{{-- MODAL CREAR / ASIGNAR USUARIO --}}
<div class="modal fade" id="modalUsuarioMiembro" tabindex="-1" aria-labelledby="modalUsuarioMiembroLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioMiembroLabel">
                    <i class="fa-solid fa-user-lock me-2"></i>
                    Usuario de acceso
                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <form method="POST" id="formUsuarioMiembro" novalidate autocomplete="off">
                @csrf

                <div class="modal-body">
                    <p class="text-secondary mb-3">
                        Usuario para que
                        <strong id="usuario_miembro_nombre">este miembro</strong>
                        pueda entrar al sistema. Se le asigna el rol "usuario"; puedes cambiarlo en Usuarios.
                    </p>

                    @if (($usuariosLibres ?? collect())->isNotEmpty())
                    <div class="btn-group w-100 mb-3" role="group" aria-label="Modo">
                        <input type="radio" class="btn-check" name="modo" id="usuario_modo_crear" value="crear" checked>
                        <label class="btn btn-outline-primary" for="usuario_modo_crear">Crear usuario nuevo</label>

                        <input type="radio" class="btn-check" name="modo" id="usuario_modo_vincular" value="vincular">
                        <label class="btn btn-outline-primary" for="usuario_modo_vincular">Vincular existente</label>
                    </div>
                    @else
                    <input type="hidden" name="modo" value="crear">
                    @endif

                    <div id="usuario_bloque_crear">
                        <div class="mb-3">
                            <label for="usuario_miembro_username" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="usuario_miembro_username" name="username"
                                maxlength="50" placeholder="Ej. juan.perez" autocomplete="off">
                            <div class="invalid-feedback" id="usuario-miembro-username-error"></div>
                        </div>

                        <div class="mb-3">
                            <label for="usuario_miembro_password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="usuario_miembro_password" name="password"
                                placeholder="Ingresa la contraseña" autocomplete="new-password">
                            <div class="invalid-feedback" id="usuario-miembro-password-error"></div>
                            <div class="form-text">{{ $politicaPassword['descripcion'] ?? '' }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="usuario_miembro_password_confirmation" class="form-label">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="usuario_miembro_password_confirmation"
                                name="password_confirmation" placeholder="Confirma la contraseña" autocomplete="new-password">
                            <div class="invalid-feedback" id="usuario-miembro-password-confirmation-error"></div>
                        </div>

                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="usuario_miembro_activo"
                                name="activo" value="1" checked>
                            <label class="form-check-label" for="usuario_miembro_activo">Usuario activo</label>
                        </div>
                    </div>

                    @if (($usuariosLibres ?? collect())->isNotEmpty())
                    <div id="usuario_bloque_vincular" class="d-none">
                        <label for="usuario_miembro_existente" class="form-label">Usuario sin persona asignada</label>
                        <select class="form-select" id="usuario_miembro_existente" name="usuario_id">
                            <option value="">Selecciona un usuario</option>
                            @foreach ($usuariosLibres as $libre)
                            <option value="{{ $libre->id }}">{{ $libre->username }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="usuario-miembro-existente-error"></div>
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

@endsection