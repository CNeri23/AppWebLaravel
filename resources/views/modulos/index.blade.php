@extends('layouts.app')
@section('title', 'Módulos')
@section('content')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Módulos</h2>
            <p class="text-secondary mb-0">Despliega y haz click derecho sobre un elemento del árbol para ver las opciones.</p>
        </div>

        @if ($accionesModulos->contains('modulos.crear'))
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevoModulo">
                <i class="fa-solid fa-layer-group me-2"></i>
                Nuevo módulo
            </button>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="modulos-treeview" id="modulosTree">
                @forelse ($modulos as $modulo)
                    <div class="tree-node">
                        {{-- MÓDULO --}}
                        <div class="tree-item modulo-tree-item {{ !$modulo->activo ? 'is-inactive' : '' }}" tabindex="0"
                            data-id="{{ $modulo->id }}" data-nombre="{{ $modulo->nombre }}" data-slug="{{ $modulo->slug }}"
                            data-descripcion="{{ $modulo->descripcion }}" data-icono="{{ $modulo->icono }}"
                            data-orden="{{ $modulo->orden }}" data-activo="{{ $modulo->activo ? '1' : '0' }}"
                            data-first="{{ $loop->first ? '1' : '0' }}" data-last="{{ $loop->last ? '1' : '0' }}"
                            data-toggle-url="{{ route('modulos.toggle', $modulo) }}"
                            data-edit-url="{{ route('modulos.update', $modulo) }}"
                            data-delete-url="{{ route('modulos.destroy', $modulo) }}"
                            data-reorder-url="{{ route('modulos.reordenar', $modulo) }}">

                            <span class="tree-folder">
                                <i class="fa-solid fa-folder"></i>
                            </span>

                            <span class="tree-icon">
                                @if ($modulo->icono)
                                    {!! $modulo->icono !!}
                                @else
                                    <i class="fa-solid fa-layer-group"></i>
                                @endif
                            </span>

                            <span class="tree-name">
                                {{ $modulo->nombre }}
                            </span>

                            @unless ($modulo->activo)
                                <span class="tree-badge">Inactivo</span>
                            @endunless

                            @if ($accionesModulos->intersect(['modulos.activar', 'modulos.editar', 'modulos.reordenar', 'submodulos.crear', 'modulos.eliminar'])->isNotEmpty())
                                <button type="button" class="tree-more-btn" data-action="abrir-menu"
                                    aria-label="Más acciones para {{ $modulo->nombre }}">

                                    <i class="fa-solid fa-ellipsis-vertical"></i>

                                </button>
                            @endif
                        </div>

                        @if ($modulo->submodulos->isNotEmpty())
                            <div class="tree-children">
                                @foreach ($modulo->submodulos as $submodulo)
                                    <div class="tree-child-node">
                                        {{-- SUBMÓDULO --}}
                                        <div class="tree-item submodulo-tree-item {{ !$submodulo->activo ? 'is-inactive' : '' }}"
                                            tabindex="0" data-id="{{ $submodulo->id }}" data-modulo-id="{{ $modulo->id }}"
                                            data-modulo-nombre="{{ $modulo->nombre }}" data-nombre="{{ $submodulo->nombre }}"
                                            data-slug="{{ $submodulo->slug }}" data-descripcion="{{ $submodulo->descripcion }}"
                                            data-icono="{{ $submodulo->icono }}" data-ruta="{{ $submodulo->ruta }}"
                                            data-orden="{{ $submodulo->orden }}" data-activo="{{ $submodulo->activo ? '1' : '0' }}"
                                            data-first="{{ $loop->first ? '1' : '0' }}" data-last="{{ $loop->last ? '1' : '0' }}"
                                            data-toggle-url="{{ route('submodulos.toggle', $submodulo) }}"
                                            data-edit-url="{{ route('submodulos.update', $submodulo) }}"
                                            data-delete-url="{{ route('submodulos.destroy', $submodulo) }}"
                                            data-reorder-url="{{ route('submodulos.reordenar', $submodulo) }}">

                                            <span class="tree-folder tree-subfolder">
                                                <i class="fa-solid fa-folder"></i>
                                            </span>

                                            <span class="tree-icon">
                                                @if ($submodulo->icono)
                                                    {!! $submodulo->icono !!}
                                                @else
                                                    <i class="fa-solid fa-circle-dot"></i>
                                                @endif
                                            </span>

                                            <span class="tree-name">
                                                {{ $submodulo->nombre }}
                                            </span>

                                            @unless ($submodulo->activo)
                                                <span class="tree-badge">Inactivo</span>
                                            @endunless

                                            @if ($accionesModulos->intersect(['submodulos.activar', 'submodulos.editar', 'submodulos.reordenar', 'acciones.crear', 'submodulos.eliminar'])->isNotEmpty())
                                                <button type="button" class="tree-more-btn submodulo-more-btn" data-action="abrir-menu"
                                                    aria-label="Más acciones para {{ $submodulo->nombre }}">

                                                    <i class="fa-solid fa-ellipsis-vertical"></i>

                                                </button>
                                            @endif
                                        </div>

                                        {{-- ACCIONES --}}
                                        @if ($submodulo->acciones->isNotEmpty())
                                            <div class="action-children">
                                                @foreach ($submodulo->acciones as $accion)
                                                    <div class="action-tree-node">
                                                        <div class="tree-item accion-tree-item {{ !$accion->activo ? 'is-inactive' : '' }}"
                                                            tabindex="0" data-id="{{ $accion->id }}" data-submodulo-id="{{ $submodulo->id }}"
                                                            data-submodulo-nombre="{{ $submodulo->nombre }}" data-nombre="{{ $accion->nombre }}"
                                                            data-slug="{{ $accion->slug }}" data-descripcion="{{ $accion->descripcion }}"
                                                            data-icono="{{ $accion->icono }}" data-orden="{{ $accion->orden }}"
                                                            data-activo="{{ $accion->activo ? '1' : '0' }}"
                                                            data-first="{{ $loop->first ? '1' : '0' }}"
                                                            data-last="{{ $loop->last ? '1' : '0' }}"
                                                            data-toggle-url="{{ route('acciones.toggle', $accion) }}"
                                                            data-edit-url="{{ route('acciones.update', $accion) }}"
                                                            data-delete-url="{{ route('acciones.destroy', $accion) }}"
                                                            data-reorder-url="{{ route('acciones.reordenar', $accion) }}">

                                                            <span class="tree-folder tree-action-folder">
                                                                <i class="fa-solid fa-file-lines"></i>
                                                            </span>

                                                            <span class="tree-icon tree-action-icon">
                                                                @if ($accion->icono)
                                                                    {!! $accion->icono !!}
                                                                @else
                                                                    <i class="fa-solid fa-bolt"></i>
                                                                @endif
                                                            </span>

                                                            <span class="tree-name">
                                                                {{ $accion->nombre }}
                                                            </span>

                                                            @unless ($accion->activo)
                                                                <span class="tree-badge">Inactiva</span>
                                                            @endunless

                                                            @if ($accionesModulos->intersect(['acciones.activar', 'acciones.editar', 'acciones.reordenar', 'acciones.eliminar'])->isNotEmpty())
                                                                <button type="button" class="tree-more-btn accion-more-btn" data-action="abrir-menu"
                                                                    aria-label="Más acciones para {{ $accion->nombre }}">

                                                                    <i class="fa-solid fa-ellipsis-vertical"></i>

                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="text-secondary">
                            <i class="fa-solid fa-layer-group fa-2x mb-3"></i>
                            <p class="mb-0">No hay módulos registrados.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>


    {{-- MENÚ CONTEXTUAL MÓDULO --}}
    <div class="context-menu" id="moduloContextMenu">
        @if ($accionesModulos->contains('modulos.toggle'))
            <div class="context-menu-item" data-action="toggle">
                <i class="fa-solid fa-power-off"></i>
                <span class="context-menu-label" data-role="toggle-label">Desactivar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('modulos.editar'))
            <div class="context-menu-item" data-action="editar">
                <i class="fa-solid fa-pen"></i>
                <span>Editar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('modulos.reordenar'))
            <div class="context-menu-divider"></div>

            <div class="context-menu-item" data-action="subir">
                <i class="fa-solid fa-arrow-up"></i>
                <span>Subir</span>
            </div>

            <div class="context-menu-item" data-action="bajar">
                <i class="fa-solid fa-arrow-down"></i>
                <span>Bajar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('submodulos.crear'))
            <div class="context-menu-divider"></div>

            <div class="context-menu-item" data-action="nuevo-submodulo">
                <i class="fa-solid fa-folder-plus"></i>
                <span>Nuevo submódulo</span>
            </div>
        @endif

        @if ($accionesModulos->contains('modulos.eliminar'))
            <div class="context-menu-divider"></div>

            <div class="context-menu-item danger" data-action="eliminar">
                <i class="fa-solid fa-trash"></i>
                <span>Eliminar</span>
            </div>
        @endif
    </div>

    {{-- MENÚ CONTEXTUAL SUBMÓDULO --}}
    <div class="context-menu" id="submoduloContextMenu">
        @if ($accionesModulos->contains('submodulos.toggle'))
            <div class="context-menu-item" data-action="toggle">
                <i class="fa-solid fa-power-off"></i>
                <span class="context-menu-label" data-role="toggle-label">Desactivar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('submodulos.editar'))
            <div class="context-menu-item" data-action="editar">
                <i class="fa-solid fa-pen"></i>
                <span>Editar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('submodulos.reordenar'))
            <div class="context-menu-divider"></div>

            <div class="context-menu-item" data-action="subir">
                <i class="fa-solid fa-arrow-up"></i>
                <span>Subir</span>
            </div>

            <div class="context-menu-item" data-action="bajar">
                <i class="fa-solid fa-arrow-down"></i>
                <span>Bajar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('acciones.crear'))
            <div class="context-menu-divider"></div>

            <div class="context-menu-item" data-action="nuevo-accion">
                <i class="fa-solid fa-bolt"></i>
                <span>Nueva acción</span>
            </div>
        @endif

        @if ($accionesModulos->contains('submodulos.eliminar'))
            <div class="context-menu-divider"></div>

            <div class="context-menu-item danger" data-action="eliminar">
                <i class="fa-solid fa-trash"></i>
                <span>Eliminar</span>
            </div>
        @endif
    </div>

    {{-- MENÚ CONTEXTUAL ACCIÓN --}}
    <div class="context-menu" id="accionContextMenu">
        @if ($accionesModulos->contains('acciones.toggle'))
            <div class="context-menu-item" data-action="toggle">
                <i class="fa-solid fa-power-off"></i>
                <span class="context-menu-label" data-role="toggle-label">Desactivar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('acciones.editar'))
            <div class="context-menu-item" data-action="editar">
                <i class="fa-solid fa-pen"></i>
                <span>Editar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('acciones.reordenar'))
            <div class="context-menu-divider"></div>

            <div class="context-menu-item" data-action="subir">
                <i class="fa-solid fa-arrow-up"></i>
                <span>Subir</span>
            </div>

            <div class="context-menu-item" data-action="bajar">
                <i class="fa-solid fa-arrow-down"></i>
                <span>Bajar</span>
            </div>
        @endif

        @if ($accionesModulos->contains('acciones.eliminar'))
            <div class="context-menu-divider"></div>

            <div class="context-menu-item danger" data-action="eliminar">
                <i class="fa-solid fa-trash"></i>
                <span>Eliminar</span>
            </div>
        @endif
    </div>

    {{-- MODAL NUEVO MÓDULO --}}
    <div class="modal fade" id="modalNuevoModulo" tabindex="-1" aria-labelledby="modalNuevoModuloLabel" aria-hidden="true"  data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoModuloLabel">
                        <i class="fa-solid fa-layer-group me-2"></i>
                        Nuevo módulo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formNuevoModulo" action="{{ route('modulos.store') }}" novalidate autocomplete="off">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>

                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre"
                                name="nombre" value="{{ old('nombre') }}" required>

                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>

                            <input type="text" class="form-control @error('slug') is-invalid @enderror" id="slug"
                                name="slug" value="{{ old('slug') }}" required>

                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>

                            <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion"
                                name="descripcion" rows="3">{{ old('descripcion') }}</textarea>

                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="icono" class="form-label">
                                Icono
                            </label>

                            <input type="text" class="form-control @error('icono') is-invalid @enderror" id="icono"
                                name="icono" value="{{ old('icono') }}">

                            @error('icono')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="alert alert-light border mt-2 mb-0 py-2">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-circle-info text-primary mt-1"></i>
                                    <div>
                                        <div class="fw-semibold small">Formato del icono</div>
                                        <div class="small text-secondary">
                                            Escribe el código HTML del icono de Font Awesome.
                                            Por ejemplo: <code>&lt;i class="fa-solid fa-users"&gt;&lt;/i&gt;</code>
                                        </div>

                                        <a href="https://fontawesome.com/search" target="_blank" rel="noopener noreferrer" class="small text-decoration-none">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>
                                            Buscar iconos en Font Awesome
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="orden" class="form-label">Orden</label>

                            <input type="number" class="form-control @error('orden') is-invalid @enderror" id="orden"
                                name="orden" value="{{ old('orden', 0) }}" min="0" required>

                            @error('orden')
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


    {{-- MODAL EDITAR MÓDULO --}}
    <div class="modal fade" id="modalEditarModulo" tabindex="-1" aria-labelledby="modalEditarModuloLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarModuloLabel">
                        <i class="fa-solid fa-pen me-2"></i>
                        Editar módulo
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formEditarModulo" novalidate autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="modal-body">
                        <input type="hidden" id="editar_id" name="id">
                        <div class="mb-3">
                            <label for="editar_nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="editar_nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="editar_slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="editar_slug" name="slug" required>
                        </div>

                        <div class="mb-3">
                            <label for="editar_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="editar_descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editar_icono" class="form-label">
                                Icono
                            </label>

                            <input type="text" class="form-control" id="editar_icono" name="icono">
                            <div class="alert alert-light border mt-2 mb-0 py-2">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-circle-info text-primary mt-1"></i>
                                    <div>
                                        <div class="fw-semibold small">
                                            Formato del icono
                                        </div>

                                        <div class="small text-secondary">
                                            Utiliza el código HTML de Font Awesome, por ejemplo:
                                            <code>&lt;i class="fa-solid fa-users"&gt;&lt;/i&gt;</code>
                                        </div>

                                        <a href="https://fontawesome.com/search" target="_blank" rel="noopener noreferrer" class="small text-decoration-none">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>
                                            Buscar iconos en Font Awesome
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="editar_orden" class="form-label">Orden</label>
                            <input type="number" class="form-control" id="editar_orden" name="orden" min="0" required>
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

    {{-- MODAL ELIMINAR MÓDULO --}}
    <div class="modal fade" id="modalEliminarModulo" tabindex="-1" aria-labelledby="modalEliminarModuloLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarModuloLabel">
                        <i class="fa-solid fa-trash text-danger me-2"></i>
                        Eliminar módulo
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formEliminarModulo">
                    @csrf
                    @method('DELETE')

                    <div class="modal-body text-center">
                        <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center"
                            style="width: 58px; height: 58px;">
                            <i class="fa-solid fa-trash fa-lg"></i>
                        </div>

                        <h6 class="fw-bold mb-2"> ¿Eliminar módulo? </h6>
                        <p class="text-secondary mb-0">
                            Estás a punto de eliminar el módulo
                            <strong id="eliminar_nombre">
                                este módulo
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

    {{-- MODAL NUEVO SUBMÓDULO --}}
    <div class="modal fade" id="modalNuevoSubmodulo" tabindex="-1" aria-labelledby="modalNuevoSubmoduloLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoSubmoduloLabel">
                        <i class="fa-solid fa-folder-plus me-2"></i>
                        Nuevo submódulo
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formNuevoSubmodulo" novalidate autocomplete="off">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="submodulo_modulo_id" name="modulo_id">

                        <div class="mb-3">
                            <label for="submodulo_modulo_nombre" class="form-label">
                                Módulo
                            </label>

                            <input type="text" class="form-control" id="submodulo_modulo_nombre" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="submodulo_nombre" class="form-label">
                                Nombre
                            </label>

                            <input type="text" class="form-control @error('nombre') is-invalid @enderror"
                                id="submodulo_nombre" name="nombre" value="{{ old('nombre') }}" required>

                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="submodulo_slug" class="form-label">
                                Slug
                            </label>

                            <input type="text" class="form-control @error('slug') is-invalid @enderror" id="submodulo_slug"
                                name="slug" value="{{ old('slug') }}" required>

                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="submodulo_ruta" class="form-label">
                                Ruta
                            </label>

                            <input type="text" class="form-control @error('ruta') is-invalid @enderror" id="submodulo_ruta"
                                name="ruta" value="{{ old('ruta') }}" placeholder="ejemplo.index">

                            @error('ruta')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-text">
                                Nombre de la ruta de Laravel que abrirá este submódulo.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="submodulo_descripcion" class="form-label">
                                Descripción
                            </label>

                            <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                id="submodulo_descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>

                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="submodulo_icono" class="form-label">
                                Icono
                            </label>

                            <input type="text" class="form-control @error('icono') is-invalid @enderror"
                                id="submodulo_icono" name="icono" value="{{ old('icono') }}">

                            @error('icono')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="alert alert-light border mt-2 mb-0 py-2">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-circle-info text-primary mt-1"></i>

                                    <div>
                                        <div class="fw-semibold small">Formato del icono</div>

                                        <div class="small text-secondary">
                                            Escribe el código HTML del icono de Font Awesome.
                                        </div>

                                        <a href="https://fontawesome.com/search" target="_blank" rel="noopener noreferrer" class="small text-decoration-none">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>
                                            Buscar iconos en Font Awesome
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="submodulo_orden" class="form-label">
                                Orden
                            </label>

                            <input type="number" class="form-control @error('orden') is-invalid @enderror"
                                id="submodulo_orden" name="orden" value="{{ old('orden', 0) }}" min="0" required>

                            @error('orden')
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

    {{-- MODAL EDITAR SUBMÓDULO --}}
    <div class="modal fade" id="modalEditarSubmodulo" tabindex="-1" aria-labelledby="modalEditarSubmoduloLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarSubmoduloLabel">
                        <i class="fa-solid fa-pen me-2"></i>
                        Editar submódulo
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formEditarSubmodulo" novalidate autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="modal-body">
                        <input type="hidden" id="editar_submodulo_id" name="id">
                        <input type="hidden" id="editar_submodulo_modulo_id" name="modulo_id">

                        <div class="mb-3">
                            <label for="editar_submodulo_modulo_nombre" class="form-label">
                                Módulo
                            </label>

                            <input type="text" class="form-control" id="editar_submodulo_modulo_nombre" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="editar_submodulo_nombre" class="form-label">
                                Nombre
                            </label>

                            <input type="text" class="form-control" id="editar_submodulo_nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="editar_submodulo_slug" class="form-label">
                                Slug
                            </label>

                            <input type="text" class="form-control" id="editar_submodulo_slug" name="slug" required>
                        </div>

                        <div class="mb-3">
                            <label for="editar_submodulo_ruta" class="form-label">
                                Ruta
                            </label>

                            <input type="text" class="form-control" id="editar_submodulo_ruta" name="ruta"
                                placeholder="ejemplo.index">

                            <div class="form-text">
                                Nombre de la ruta de Laravel que abrirá este submódulo.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editar_submodulo_descripcion" class="form-label">
                                Descripción
                            </label>

                            <textarea class="form-control" id="editar_submodulo_descripcion" name="descripcion"
                                rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editar_submodulo_icono" class="form-label">
                                Icono
                            </label>

                            <input type="text" class="form-control" id="editar_submodulo_icono" name="icono">

                            <div class="alert alert-light border mt-2 mb-0 py-2">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-circle-info text-primary mt-1"></i>
                                    
                                    <div>
                                        <div class="fw-semibold small">
                                            Formato del icono
                                        </div>

                                        <div class="small text-secondary">
                                            Escribe el código HTML del icono de Font Awesome.
                                            Por ejemplo:
                                            <code>&lt;i class="fa-solid fa-users"&gt;&lt;/i&gt;</code>
                                        </div>

                                        <a href="https://fontawesome.com/search" target="_blank" rel="noopener noreferrer" class="small text-decoration-none">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>
                                            Buscar iconos en Font Awesome
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="editar_submodulo_orden" class="form-label">
                                Orden
                            </label>

                            <input type="number" class="form-control" id="editar_submodulo_orden" name="orden" min="0" required>
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

    {{-- MODAL ELIMINAR SUBMÓDULO --}}
    <div class="modal fade" id="modalEliminarSubmodulo" tabindex="-1" aria-labelledby="modalEliminarSubmoduloLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarSubmoduloLabel">
                        <i class="fa-solid fa-trash text-danger me-2"></i>
                        Eliminar submódulo
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formEliminarSubmodulo">
                    @csrf
                    @method('DELETE')

                    <div class="modal-body text-center">
                        <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                            <i class="fa-solid fa-trash fa-lg"></i>
                        </div>

                        <h6 class="fw-bold mb-2">¿Eliminar submódulo?</h6>
                        <p class="text-secondary mb-0">
                            Estás a punto de eliminar el submódulo
                            <strong id="eliminar_submodulo_nombre">
                                este submódulo
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

    {{-- MODAL NUEVA ACCIÓN --}}
    <div class="modal fade" id="modalNuevaAccion" tabindex="-1" aria-labelledby="modalNuevaAccionLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevaAccionLabel">
                        <i class="fa-solid fa-bolt me-2"></i>
                        Nueva acción
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formNuevaAccion" novalidate autocomplete="off">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="accion_submodulo_id" name="submodulo_id">

                        <div class="mb-3">
                            <label for="accion_submodulo_nombre" class="form-label">
                                Submódulo
                            </label>

                            <input type="text" class="form-control" id="accion_submodulo_nombre" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="accion_nombre" class="form-label">
                                Nombre
                            </label>

                            <input type="text" class="form-control" id="accion_nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="accion_slug" class="form-label">
                                Slug
                            </label>

                            <input type="text" class="form-control" id="accion_slug" name="slug" required>
                        </div>

                        <div class="mb-3">
                            <label for="accion_descripcion" class="form-label">
                                Descripción
                            </label>

                            <textarea class="form-control" id="accion_descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="accion_icono" class="form-label">
                                Icono
                            </label>

                            <input type="text" class="form-control" id="accion_icono" name="icono">

                            <div class="alert alert-light border mt-2 mb-0 py-2">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-circle-info text-primary mt-1"></i>

                                    <div>
                                        <div class="fw-semibold small">Formato del icono</div>

                                        <div class="small text-secondary">
                                            Escribe el código HTML del icono de Font Awesome.
                                            Por ejemplo:
                                            <code>&lt;i class="fa-solid fa-users"&gt;&lt;/i&gt;</code>
                                        </div>

                                        <a href="https://fontawesome.com/search" target="_blank" rel="noopener noreferrer" class="small text-decoration-none">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>
                                            Buscar iconos en Font Awesome
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="accion_orden" class="form-label">
                                Orden
                            </label>

                            <input type="number" class="form-control" id="accion_orden" name="orden" value="0" min="0" required>
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

    {{-- MODAL EDITAR ACCIÓN --}}
    <div class="modal fade" id="modalEditarAccion" tabindex="-1" aria-labelledby="modalEditarAccionLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarAccionLabel">
                        <i class="fa-solid fa-pen me-2"></i>
                        Editar acción
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formEditarAccion" novalidate autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="modal-body">
                        <input type="hidden" id="editar_accion_id" name="id">
                        <div class="mb-3">
                            <label for="editar_accion_submodulo_nombre" class="form-label">
                                Submódulo
                            </label>

                            <input type="text" class="form-control" id="editar_accion_submodulo_nombre" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="editar_accion_nombre" class="form-label">
                                Nombre
                            </label>

                            <input type="text" class="form-control" id="editar_accion_nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="editar_accion_slug" class="form-label">
                                Slug
                            </label>

                            <input type="text" class="form-control" id="editar_accion_slug" name="slug" required>
                        </div>

                        <div class="mb-3">
                            <label for="editar_accion_descripcion" class="form-label">
                                Descripción
                            </label>

                            <textarea class="form-control" id="editar_accion_descripcion" name="descripcion" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="editar_accion_icono" class="form-label">
                                Icono
                            </label>

                            <input type="text" class="form-control" id="editar_accion_icono" name="icono">
                            <div class="alert alert-light border mt-2 mb-0 py-2">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="fa-solid fa-circle-info text-primary mt-1"></i>

                                    <div>
                                        <div class="fw-semibold small">Formato del icono</div>
                                        <div class="small text-secondary">
                                            Escribe el código HTML del icono de Font Awesome.
                                            Por ejemplo:
                                            <code>&lt;i class="fa-solid fa-users"&gt;&lt;/i&gt;</code>
                                        </div>

                                        <a href="https://fontawesome.com/search" target="_blank" rel="noopener noreferrer" class="small text-decoration-none">
                                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>
                                            Buscar iconos en Font Awesome
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="editar_accion_orden" class="form-label">
                                Orden
                            </label>

                            <input type="number" class="form-control" id="editar_accion_orden" name="orden" min="0" required>
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

    {{-- MODAL ELIMINAR ACCIÓN --}}
    <div class="modal fade" id="modalEliminarAccion" tabindex="-1" aria-labelledby="modalEliminarAccionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEliminarAccionLabel">
                        <i class="fa-solid fa-trash text-danger me-2"></i>
                        Eliminar acción
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>

                <form method="POST" id="formEliminarAccion">
                    @csrf
                    @method('DELETE')

                    <div class="modal-body text-center">
                        <div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                            <i class="fa-solid fa-trash fa-lg"></i>
                        </div>

                        <h6 class="fw-bold mb-2">¿Eliminar acción?</h6>
                        <p class="text-secondary mb-0">
                            Estás a punto de eliminar la acción
                            <strong id="eliminar_accion_nombre">
                                esta acción
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