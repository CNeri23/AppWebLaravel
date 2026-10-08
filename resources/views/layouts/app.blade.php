@php
    $systemSettings = app(\App\Services\SystemSettings::class);
    $settings = $systemSettings->all();

    $usuarioActual = auth()->user();

    $userPreferencesService = app(\App\Services\UserPreferences::class);
    $userPreferences = $userPreferencesService->get($usuarioActual);

    $horaActual = now($settings['timezone'])->hour;

    if ($horaActual < 12) {
        $greeting = 'Buenos días';
    } elseif ($horaActual < 19) {
        $greeting = 'Buenas tardes';
    } else {
        $greeting = 'Buenas noches';
    }

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

<html lang="es" data-bs-theme="{{ $userPreferences['theme_mode'] === 'dark' ? 'dark' : 'light' }}"
    data-theme-mode="{{ $userPreferences['theme_mode'] }}"
    data-light-theme-style="{{ $userPreferences['light_theme_style'] }}"
    data-dark-theme-style="{{ $userPreferences['dark_theme_style'] }}"
    data-theme-style="{{ $userPreferences['theme_mode'] === 'dark' ? $userPreferences['dark_theme_style'] : $userPreferences['light_theme_style'] }}"
    data-accent-color="{{ $settings['accent_color'] }}"
    data-session-timeout="{{ (int) $settings['session_timeout'] }}"
    data-login-url="{{ route('login') }}"
    data-timezone="{{ $settings['timezone'] }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', $settings['system_name'])</title>

    <script src="{{ asset('js/layout.js') }}"></script>

    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables/css/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">

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
                @if (!empty($settings['logo_path']))
                    <img src="{{ asset('storage/' . $settings['logo_path']) }}" alt="{{ $settings['system_name'] }}">
                @else
                    <i class="fa-solid fa-heart-pulse"></i>
                @endif
            </div>

            <span class="sidebar-brand-text">{{ $settings['system_name'] }}</span>
        </div>

        <div class="topbar-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" placeholder="Buscar...">
        </div>

        <div class="topbar-actions">
            <span class="topbar-greeting">
                <span id="topbarGreetingText">{{ $greeting }}</span>,
                <strong>{{ auth()->user()->name }}</strong>
            </span>

            <span class="topbar-divider"></span>

            <a href="{{ route('dashboard') }}" class="topbar-icon-btn" title="Inicio">
                <i class="fa-solid fa-house"></i>
            </a>

            <button type="button" class="theme-switch" id="themeSwitch" role="switch" aria-checked="false"
                aria-label="Cambiar entre modo claro y oscuro" title="Cambiar tema">
                <span class="theme-switch-track">
                    <span class="theme-switch-thumb">
                        <i class="fa-solid fa-sun theme-switch-sun"></i>
                        <i class="fa-solid fa-moon theme-switch-moon"></i>
                    </span>
                </span>
            </button>

            <span class="topbar-divider"></span>

            @php
                $notificaciones = collect([
                    (object) [
                        'icono' => 'fa-solid fa-user-plus',
                        'color' => 'primary',
                        'titulo' => 'Nuevo usuario registrado',
                        'descripcion' => 'Se creó una cuenta nueva en el sistema.',
                        'tiempo' => 'Hace 10 minutos',
                        'leida' => false,
                    ],
                    (object) [
                        'icono' => 'fa-solid fa-shield-halved',
                        'color' => 'warning',
                        'titulo' => 'Permisos actualizados',
                        'descripcion' => 'Se modificaron los permisos de un rol.',
                        'tiempo' => 'Hace 2 horas',
                        'leida' => false,
                    ],
                    (object) [
                        'icono' => 'fa-solid fa-circle-check',
                        'color' => 'success',
                        'titulo' => 'Respaldo completado',
                        'descripcion' => 'El respaldo automático finalizó sin errores.',
                        'tiempo' => 'Ayer',
                        'leida' => true,
                    ],
                ]);

                $notificacionesNoLeidas = $notificaciones->where('leida', false)->count();
            @endphp

            <div class="dropdown notification-dropdown">
                <button class="dropdown-toggle topbar-icon-btn" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false" title="Notificaciones">
                    <i class="fa-solid fa-bell"></i>

                    @if ($notificacionesNoLeidas > 0)
                        <span class="notification-badge">
                            {{ $notificacionesNoLeidas > 9 ? '9+' : $notificacionesNoLeidas }}
                        </span>
                    @endif
                </button>

                <div class="dropdown-menu dropdown-menu-end notification-menu">
                    <div class="notification-menu-header">
                        <strong>Notificaciones</strong>

                        @if ($notificacionesNoLeidas > 0)
                            <span class="text-secondary small">
                                {{ $notificacionesNoLeidas }} sin leer
                            </span>
                        @endif
                    </div>

                    <div class="notification-list">
                        @forelse ($notificaciones as $notificacion)
                            <div class="notification-item {{ $notificacion->leida ? '' : 'is-unread' }}">
                                <div
                                    class="notification-icon bg-{{ $notificacion->color }}-subtle text-{{ $notificacion->color }}">
                                    <i class="{{ $notificacion->icono }}"></i>
                                </div>

                                <div class="notification-body">
                                    <div class="notification-title">
                                        {{ $notificacion->titulo }}
                                    </div>

                                    <div class="notification-description">
                                        {{ $notificacion->descripcion }}
                                    </div>

                                    <div class="notification-time">
                                        {{ $notificacion->tiempo }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="notification-empty">
                                <i class="fa-regular fa-bell-slash"></i>
                                <p class="mb-0">No tienes notificaciones.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <span class="topbar-divider"></span>

            <div class="dropdown user-dropdown">
                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                    title="{{ auth()->user()->name }}">
                    @if (auth()->user()->profile_image)
                        <img src="{{ asset('storage/' . auth()->user()->profile_image) }}"
                            alt="Foto de {{ auth()->user()->name }}" class="user-avatar" id="topbarUserAvatar">
                    @else
                        <div class="user-avatar" id="topbarUserAvatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    @endif
                </button>

                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="user-dropdown-header">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>{{ auth()->user()->email }}</small>
                    </li>

                    <li>
                        <a href="{{ route('perfil') }}" class="dropdown-item">
                            <i class="fa-regular fa-user"></i>
                            Mi perfil
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('preferencias.index') }}" class="dropdown-item">
                            <i class="fa-solid fa-sliders"></i>
                            Preferencias
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

    @stack('scripts')
</body>

</html>