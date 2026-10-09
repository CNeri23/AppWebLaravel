@extends('layouts.app')

@section('content')

@php
    $accionesAsistencias = $accionesAsistencias ?? collect();

    $puedeRegistrar = $accionesAsistencias->contains('slug', 'asistencias.registrar');
    $puedeAnular = $accionesAsistencias->contains('slug', 'asistencias.eliminar');

    $iconoAnular = optional($accionesAsistencias->firstWhere('slug', 'asistencias.eliminar'))->icono
        ?: '<i class="fa-regular fa-trash-can"></i>';
@endphp

<div id="asistenciasApp"
    data-url-buscar="{{ route('asistencias.buscar') }}"
    data-url-registrar="{{ route('asistencias.store') }}"
    data-incluye-hoy="{{ $incluyeHoy ? 1 : 0 }}"
    data-puede-anular="{{ $puedeAnular ? 1 : 0 }}"
    data-icono-anular="{{ $iconoAnular }}">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1">Asistencias</h1>
            <p class="text-secondary mb-0">Registro de entradas de los miembros al gimnasio.</p>
        </div>
    </div>

    @if ($puedeRegistrar)
        <div class="card border-0 shadow-sm mb-4 asistencia-registro-card">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>
                    Registrar entrada
                </h5>

                <form id="formBuscarAsistencia" autocomplete="off" novalidate>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>

                        <input type="search" id="asistenciaBusqueda" class="form-control"
                            placeholder="Nombre, teléfono, correo o número de miembro" autofocus>

                        <button type="submit" class="btn btn-primary">
                            Buscar
                        </button>
                    </div>

                    <div class="form-text">
                        Escribe y presiona Enter: si hay un solo resultado se registra la entrada al instante.
                    </div>
                </form>

                <div id="asistenciaResultados" class="list-group asistencias-resultados mt-3 d-none"></div>
                <div id="asistenciaResultado" class="alert asistencia-resultado mt-3 mb-0 d-none" role="alert"></div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100 asistencia-kpi-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="asistencia-kpi-icono bg-success-subtle text-success">
                        <i class="fa-solid fa-person-walking-arrow-right"></i>
                    </div>

                    <div>
                        <div class="text-secondary small">Entradas</div>
                        <div class="asistencia-kpi-valor" id="kpiEntradas">{{ $resumen['entradas'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="asistencia-kpi-icono bg-primary-subtle text-primary">
                        <i class="fa-solid fa-user-check"></i>
                    </div>

                    <div>
                        <div class="text-secondary small">Miembros distintos</div>
                        <div class="asistencia-kpi-valor" id="kpiMiembros">{{ $resumen['miembros'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="asistencia-kpi-icono bg-danger-subtle text-danger">
                        <i class="fa-solid fa-ban"></i>
                    </div>

                    <div>
                        <div class="text-secondary small">Accesos denegados</div>
                        <div class="asistencia-kpi-valor" id="kpiDenegadas">{{ $resumen['denegadas'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm asistencia-registros-card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                <div>
                    <h5 class="fw-semibold mb-1 asistencia-registros-titulo">
                        <i class="fa-solid fa-clock-rotate-left me-2"></i>
                        Registros
                    </h5>

                    <div class="text-secondary small">{{ $etiquetaRango }}</div>
                </div>

                <form method="GET" action="{{ route('asistencias.index') }}" class="row g-2 align-items-end asistencia-filtros">
                    <div class="col-auto">
                        <label for="filtroDesde" class="form-label small mb-1">Desde</label>
                        <input type="date" class="form-control form-control-sm" id="filtroDesde" name="desde"
                            value="{{ $desde }}">
                    </div>

                    <div class="col-auto">
                        <label for="filtroHasta" class="form-label small mb-1">Hasta</label>
                        <input type="date" class="form-control form-control-sm" id="filtroHasta" name="hasta"
                            value="{{ $hasta }}">
                    </div>

                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-filter me-1"></i>
                            Filtrar
                        </button>

                        <a href="{{ route('asistencias.index') }}" class="btn btn-sm btn-outline-secondary">
                            Hoy
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive asistencias-table-wrap">
                <table id="tablaAsistencias" class="table table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Miembro</th>
                            <th>Plan</th>
                            <th>Resultado</th>
                            <th>Registró</th>
                            @if ($puedeAnular)
                                <th class="text-center">Acciones</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($registros as $registro)
                            <tr data-id="{{ $registro['id'] }}" data-persona="{{ $registro['persona_id'] }}"
                                data-permitido="{{ $registro['permitido'] ? 1 : 0 }}">
                                <td data-order="{{ $registro['timestamp'] }}">
                                    <div class="fw-semibold">{{ $registro['hora'] }}</div>
                                    <div class="small text-secondary">{{ $registro['fecha'] }}</div>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $registro['nombre'] }}</div>

                                    @if ($registro['email'])
                                        <div class="small text-secondary">{{ $registro['email'] }}</div>
                                    @endif
                                </td>

                                <td>{{ $registro['plan'] ?: '—' }}</td>

                                <td>
                                    @if ($registro['permitido'])
                                        <span class="badge rounded-pill text-bg-success">Permitido</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-danger">Denegado</span>

                                        @if ($registro['motivo'])
                                            <div class="small text-secondary mt-1">{{ $registro['motivo'] }}</div>
                                        @endif
                                    @endif
                                </td>

                                <td>{{ $registro['usuario'] ?: '—' }}</td>

                                @if ($puedeAnular)
                                    <td class="text-center">
                                        <div class="asistencia-actions">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger asistencia-action-btn btn-anular-asistencia"
                                                title="Anular registro" data-url="{{ $registro['url_eliminar'] }}"
                                                data-nombre="{{ $registro['nombre'] }}">
                                                {!! $iconoAnular !!}
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection