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

            <a href="{{ route('usuarios.index') }}" data-label="Usuarios" class="sidebar-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i>
                <span>Usuarios</span>
            </a>

            <a href="#" data-label="Roles" class="sidebar-link">
                <i class="fa-solid fa-user-shield"></i>
                <span>Roles</span>
            </a>

            <a href="#" data-label="Permisos" class="sidebar-link">
                <i class="fa-solid fa-key"></i>
                <span>Permisos</span>
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
    <div class="app-main">
        <header class="app-topbar">
            <div class="topbar-left">
                <button type="button" class="topbar-icon-btn mobile-menu-btn" id="mobileMenuBtn"
                    title="Abrir menú">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <div class="topbar-actions">
                <div class="dropdown theme-dropdown">
                    <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                        title="Cambiar tema">
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
                    <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>

                        <div class="user-info">
                            <span class="user-name">
                                {{ auth()->user()->name }}
                            </span>

                            <span class="user-role">
                                Administrador
                            </span>
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
    <script src="{{ asset('js/layout.js') }}"></script>

    @stack('scripts')

</body>

</html>