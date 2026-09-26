@php
    $hour = now()->format('G');
    if ($hour < 12) {
        $greeting = 'Buenos días';
    } elseif ($hour < 19) {
        $greeting = 'Buenas tardes';
    } else {
        $greeting = 'Buenas noches';
    }
@endphp
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Panel administrativo')</title>

    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>

<body>
    <aside class="app-sidebar" id="appSidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-group">
                <div class="sidebar-brand-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <span class="sidebar-brand-text">
                    Admin Panel
                </span>
            </div>

            <button type="button" class="sidebar-collapse-toggle" data-sidebar-collapse-toggle
                title="Colapsar / expandir menú">
                <i class="fa-solid fa-angles-left"></i>
            </button>
        </div>

        <nav class="sidebar-menu">
            <div class="sidebar-section">
                <span>Principal</span>
            </div>

            <a href="{{ route('dashboard') }}" data-label="Dashboard"
                class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>

            <div class="sidebar-section">
                <span>Administración</span>
            </div>

            <a href="{{ route('usuarios.index') }}" data-label="Usuarios"
                class="sidebar-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>Usuarios</span>
            </a>

            <a href="{{ route('roles.index') }}" data-label="Roles"
                class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-shield"></i>
                <span>Roles</span>
            </a>

            <a href="#" data-label="Permisos" class="sidebar-link">
                <i class="fa-solid fa-key"></i>
                <span>Permisos</span>
            </a>

            <a href="{{ route('logs.index') }}" data-label="Logs"
                class="sidebar-link {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Logs</span>
            </a>

            <div class="sidebar-section">
                <span>Sistema</span>
            </div>

            <a href="#" data-label="Configuración" class="sidebar-link">
                <i class="fa-solid fa-gear"></i>
                <span>Configuración</span>
            </a>

        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-version">
                <i class="fa-solid fa-code"></i>
                <span>Laravel {{ app()->version() }}</span>
            </div>
        </div>

    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>

    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-actions">
                <span class="topbar-greeting">
                    {{ $greeting }}, <strong>{{ auth()->user()->name }}</strong>
                </span>

                <span class="topbar-divider"></span>

                <a href="{{ route('dashboard') }}" class="topbar-icon-btn" title="Inicio">
                    <i class="fa-solid fa-house"></i>
                </a>

                <div class="dropdown theme-dropdown">
                    <button class="dropdown-toggle topbar-icon-btn" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" title="Cambiar tema">
                        <i class="fa-solid fa-sun" id="themeIcon"></i>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button type="button" class="dropdown-item theme-option" data-theme="light">
                                <i class="fa-solid fa-sun"></i>
                                Claro
                            </button>
                        </li>

                        <li>
                            <button type="button" class="dropdown-item theme-option" data-theme="dark">
                                <i class="fa-solid fa-moon"></i>
                                Oscuro
                            </button>
                        </li>

                        <li>
                            <button type="button" class="dropdown-item theme-option" data-theme="auto">
                                <i class="fa-solid fa-circle-half-stroke"></i>
                                Automático
                            </button>
                        </li>
                    </ul>
                </div>

                <span class="topbar-divider"></span>
                <div class="dropdown user-dropdown">
                    <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                        title="{{ auth()->user()->name }}">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="user-dropdown-header">
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ auth()->user()->email }}</small>
                        </li>

                        <li>
                            <a href="#" class="dropdown-item">
                                <i class="fa-regular fa-user"></i>
                                Mi perfil
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item logout-item">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                    Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="app-content">
            @yield('content')
        </main>
    </div>

    <script src="{{ asset('vendor/datatables/js/datatables.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/toast.js') }}"></script>
    <script src="{{ asset('js/layout.js') }}"></script>

    @stack('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if (session('success'))
                window.showToast('success', @json(session('success')));
            @endif

            @if (session('error'))
                window.showToast('error', @json(session('error')));
            @endif

            @if (session('warning'))
                window.showToast('warning', @json(session('warning')));
            @endif

            @if (session('info'))
                window.showToast('info', @json(session('info')));
            @endif

            @if ($errors->any())
                window.showToast('error', @json($errors->all()), {
                    title: 'Hay algunos errores en el formulario',
                });
            @endif
        });
    </script>

</body>

</html>