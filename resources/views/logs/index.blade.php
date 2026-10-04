@extends('layouts.app')
@section('content')

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>
                    Logs del sistema
                </h1>

                <p class="text-muted mb-0">Historial de acciones realizadas en el sistema.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaLogs" class="table table-hover align-middle mb-0" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Usuario</th>
                                <th>Módulo</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                                <th>IP</th>
                                <th>Navegador</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($logs as $log)
                                <tr>
                                    <td data-order="{{ $log->created_at->timestamp }}">
                                        {{ $log->created_at->timezone($timezone)->format($dateFormat . ' ' . $timeFormat) }}
                                    </td>
                                    <td>
                                        {{ $log->user?->name ?? 'Usuario no disponible' }}
                                    </td>
                                    <td>
                                        {{ $log->module }}
                                    </td>
                                    <td>
                                        {{ $log->action }}
                                    </td>
                                    <td>
                                        {{ $log->description }}
                                    </td>
                                    <td>
                                        {{ $log->ip_address ?? 'No disponible' }}
                                    </td>
                                    <td title="{{ $log->user_agent ?? 'No disponible' }}">
                                        {{ $log->user_agent ?? 'No disponible' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection