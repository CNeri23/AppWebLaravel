@extends('layouts.app')
@section('content')

    <div class="mb-4">
        <h1 class="fw-bold mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Resumen general del sistema.</p>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-primary-subtle text-primary p-3">
                            <i class="fa-solid fa-user fa-lg"></i>
                        </div>

                        <div class="ms-3">
                            <h5 class="mb-1">Usuario</h5>
                            <small class="text-secondary">Sesión actual</small>
                        </div>
                    </div>

                    <p class="mb-2">
                        <strong>Nombre:</strong>
                        {{ auth()->user()->name }}
                    </p>

                    <p class="mb-0">
                        <strong>Correo:</strong>
                        {{ auth()->user()->email }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-success-subtle text-success p-3">
                            <i class="fa-solid fa-users fa-lg"></i>
                        </div>

                        <div class="ms-3">
                            <h5 class="mb-1">Usuarios</h5>
                            <small class="text-secondary">Administración</small>
                        </div>
                    </div>

                    <p class="text-secondary mb-0">
                        Gestiona los usuarios registrados en el sistema.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-3 bg-warning-subtle text-warning p-3">
                            <i class="fa-solid fa-shield-halved fa-lg"></i>
                        </div>

                        <div class="ms-3">
                            <h5 class="mb-1">Seguridad</h5>
                            <small class="text-secondary">Roles y permisos</small>
                        </div>
                    </div>

                    <p class="text-secondary mb-0">
                        Administra los roles y permisos del sistema.
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection