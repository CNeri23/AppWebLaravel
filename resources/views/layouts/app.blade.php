@php
    $horaActual = now('America/Mexico_City')->hour;

    if ($horaActual < 12) {
        $greeting = 'Buenos días';
    } elseif ($horaActual < 19) {
        $greeting = 'Buenas tardes';
    } else {
        $greeting = 'Buenas noches';
    }

    $usuarioActual = auth()->user();

    $permisosUsuario = $usuarioActual->permissions();

    $modulosPermitidos = $permisosUsuario
        ->where('permission_type', 'modulo')
        ->pluck('permission_id');

    $submodulosPermitidos = $permisosUsuario
        ->where('permission_type', 'submodulo')
        ->pluck('permission_id');

    $modulosMenu = \App\Models\Modulo::with([
        'submodulos' => function ($query) use ($submodulosPermitidos) {
            $query
                ->where('activo', true)
                ->whereIn('id', $submodulosPermitidos)
                ->orderBy('orden')
                ->orderBy('nombre');
        }
    ])
        ->where('activo', true)
        ->whereIn('id', $modulosPermitidos)
        ->orderBy('orden')
        ->orderBy('nombre')
        ->get()
        ->filter(function ($modulo) {
            return $modulo->submodulos->isNotEmpty();
        });

@endphp

<!DOCTYPE html>

<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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
    <header class="app-topbar">
        <div class="topbar-brand">
            <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Colapsar / expandir menú">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="sidebar-brand-icon">
                <i class="fa-solid fa-layer-group"></i>
            </div>

            <span class="sidebar-brand-text">Admin Panel</span>
        </div>

        <div class="topbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar...">
        </div>

        <div class="topbar-actions">
            <span class="topbar-greeting">
                {{ $greeting }},
                <strong>{{ auth()->user()->name }}</strong>
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

    <aside class="app-sidebar" id="appSidebar">
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

            @foreach ($modulosMenu as $modulo)
                @php
                    $idAcordeon = 'moduloMenu' . $modulo->id;

                    $tieneSubmoduloActivo = $modulo->submodulos->contains(
                        fn($submodulo) =>
                            $submodulo->ruta &&
                            request()->routeIs($submodulo->ruta)
                    );
                @endphp

                {{-- Módulo --}}
                <div class="sidebar-group">
                    <button type="button" class="sidebar-group-toggle {{ $tieneSubmoduloActivo ? 'active' : '' }}"
                        data-modulo-id="{{ $modulo->id }}" data-bs-toggle="collapse" data-bs-target="#{{ $idAcordeon }}"
                        data-label="{{ $modulo->nombre }}" aria-expanded="{{ $tieneSubmoduloActivo ? 'true' : 'false' }}"
                        aria-controls="{{ $idAcordeon }}">

                        @if ($modulo->icono)
                            {!! $modulo->icono !!}
                        @else
                            <i class="fa-solid fa-layer-group"></i>
                        @endif

                        <span>{{ $modulo->nombre }}</span>

                        <i class="fa-solid fa-chevron-down sidebar-group-caret"></i>
                    </button>

                    {{-- Submódulos --}}
                    <div class="collapse sidebar-submenu {{ $tieneSubmoduloActivo ? 'show' : '' }}" id="{{ $idAcordeon }}"
                        data-modulo-id="{{ $modulo->id }}">

                        @foreach ($modulo->submodulos as $submodulo)
                            @php
                                $url = $submodulo->ruta &&
                                    \Illuminate\Support\Facades\Route::has($submodulo->ruta)
                                    ? route($submodulo->ruta)
                                    : '#';

                                $esActivo = $url !== '#' &&
                                    request()->routeIs($submodulo->ruta);
                            @endphp

                            <a href="{{ $url }}"
                                class="sidebar-sublink {{ $esActivo ? 'active' : '' }} {{ $url === '#' ? 'sidebar-link-pendiente' : '' }}"
                                data-submodulo-id="{{ $submodulo->id }}" data-modulo-id="{{ $modulo->id }}" @if ($url === '#')
                                title="Este submódulo aún no tiene una ruta configurada" @endif>

                                @if ($submodulo->icono)
                                    {!! $submodulo->icono !!}
                                @else
                                    <i class="fa-solid fa-circle-dot"></i>
                                @endif

                                <span>{{ $submodulo->nombre }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="sidebar-section">
                <span>Sistema</span>
            </div>

            <a href="#" data-label="Configuración" class="sidebar-link">
                <i class="fa-solid fa-gear"></i>
                <span>Configuración</span>
            </a>
        </nav>
    </aside>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true">
    </div>

    <div class="app-main">
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