@extends('layouts.app')
@section('content')

<div class="container-fluid py-4 preferencias-page">
    <div class="preferences-card">

        @php
            $lightThemeStyles = [
                'white' => [
                    'name' => 'White',
                    'description' => 'Claro limpio',
                    'class' => 'theme-preview-white',
                    'toggle' => 'light',
                ],
                'mist' => [
                    'name' => 'Mist',
                    'description' => 'Claro suave',
                    'class' => 'theme-preview-mist',
                    'toggle' => 'light',
                ],
                'sky' => [
                    'name' => 'Sky',
                    'description' => 'Claro fresco',
                    'class' => 'theme-preview-sky',
                    'toggle' => 'light',
                ],
            ];

            $darkThemeStyles = [
                'graphite' => [
                    'name' => 'Graphite',
                    'description' => 'Oscuro elegante',
                    'class' => 'theme-preview-graphite',
                    'toggle' => 'dark',
                ],
                'charcoal' => [
                    'name' => 'Charcoal',
                    'description' => 'Oscuro neutro',
                    'class' => 'theme-preview-charcoal',
                    'toggle' => 'dark',
                ],
                'black' => [
                    'name' => 'Black',
                    'description' => 'Negro profundo',
                    'class' => 'theme-preview-black',
                    'toggle' => 'dark',
                ],
            ];
        @endphp

        <section class="theme-section">
            <div class="theme-section-header">
                <div>
                    <h2>Tema claro</h2>
                    <p>Selecciona el estilo que quieres utilizar cuando estés en modo claro.</p>
                </div>
            </div>

            <div class="theme-style-grid">
                @foreach ($lightThemeStyles as $value => $style)
                    <label class="theme-style-option">
                        <input
                            type="radio"
                            name="light_theme_style"
                            value="{{ $value }}"
                            {{ $preferencias['light_theme_style'] === $value ? 'checked' : '' }}
                        >

                        <span class="theme-style-content">
                            <span class="theme-preview {{ $style['class'] }}">

                                <span class="theme-preview-topbar">
                                    <span class="theme-preview-brand">
                                        <span class="theme-preview-brand-icon">
                                            <i class="fa-solid fa-heart-pulse"></i>
                                        </span>

                                        <span class="theme-preview-brand-line"></span>
                                    </span>

                                    <span class="theme-preview-search">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <span></span>
                                    </span>

                                    <span class="theme-preview-topbar-actions">
                                        <span class="theme-preview-topbar-icon">
                                            <i class="fa-solid fa-house"></i>
                                        </span>

                                        <span class="theme-preview-toggle {{ $style['toggle'] }}">
                                            <span class="theme-preview-toggle-icon theme-preview-toggle-sun">
                                                <i class="fa-solid fa-sun"></i>
                                            </span>

                                            <span class="theme-preview-toggle-icon theme-preview-toggle-moon">
                                                <i class="fa-solid fa-moon"></i>
                                            </span>

                                            <span class="theme-preview-toggle-thumb"></span>
                                        </span>

                                        <span class="theme-preview-topbar-icon">
                                            <i class="fa-solid fa-bell"></i>
                                        </span>

                                        <span class="theme-preview-avatar">C</span>
                                    </span>
                                </span>

                                <span class="theme-preview-sidebar">
                                    <span class="theme-preview-sidebar-section">
                                        Principal
                                    </span>

                                    <span class="theme-preview-sidebar-link active">
                                        <i class="fa-solid fa-gauge-high"></i>
                                        <span>Dashboard</span>
                                    </span>

                                    <span class="theme-preview-sidebar-section">
                                        Administración
                                    </span>

                                    <span class="theme-preview-sidebar-link">
                                        <i class="fa-solid fa-users"></i>
                                        <span>Usuarios</span>
                                        <i class="fa-solid fa-chevron-down theme-preview-caret"></i>
                                    </span>

                                    <span class="theme-preview-sidebar-link">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        <span>Roles</span>
                                    </span>

                                    <span class="theme-preview-sidebar-link">
                                        <i class="fa-solid fa-gear"></i>
                                        <span>Configuración</span>
                                    </span>
                                </span>

                                <span class="theme-preview-main">

                                    <span class="theme-preview-main-header">
                                        <span>
                                            <span class="theme-preview-title"></span>
                                            <span class="theme-preview-subtitle"></span>
                                        </span>

                                        <span class="theme-preview-action">
                                            <i class="fa-solid fa-plus"></i>
                                        </span>
                                    </span>

                                    <span class="theme-preview-stats">
                                        <span class="theme-preview-stat">
                                            <span class="theme-preview-stat-icon">
                                                <i class="fa-solid fa-users"></i>
                                            </span>

                                            <span class="theme-preview-stat-content">
                                                <span></span>
                                                <strong></strong>
                                            </span>
                                        </span>

                                        <span class="theme-preview-stat">
                                            <span class="theme-preview-stat-icon">
                                                <i class="fa-solid fa-calendar-check"></i>
                                            </span>

                                            <span class="theme-preview-stat-content">
                                                <span></span>
                                                <strong></strong>
                                            </span>
                                        </span>

                                        <span class="theme-preview-stat">
                                            <span class="theme-preview-stat-icon">
                                                <i class="fa-solid fa-chart-line"></i>
                                            </span>

                                            <span class="theme-preview-stat-content">
                                                <span></span>
                                                <strong></strong>
                                            </span>
                                        </span>
                                    </span>

                                    <span class="theme-preview-panel">
                                        <span class="theme-preview-panel-header">
                                            <span></span>
                                            <span></span>
                                        </span>

                                        <span class="theme-preview-chart">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </span>
                                    </span>

                                </span>

                            </span>

                            <span class="theme-style-info">
                                <span class="theme-style-info-text">
                                    <strong>{{ $style['name'] }}</strong>
                                    <small>{{ $style['description'] }}</small>
                                </span>

                                <span class="theme-style-check">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

        <div class="theme-section-divider"></div>

        <section class="theme-section">
            <div class="theme-section-header">
                <div>
                    <h2>Tema oscuro</h2>
                    <p>Selecciona el estilo que quieres utilizar cuando estés en modo oscuro.</p>
                </div>
            </div>

            <div class="theme-style-grid">
                @foreach ($darkThemeStyles as $value => $style)
                    <label class="theme-style-option">
                        <input
                            type="radio"
                            name="dark_theme_style"
                            value="{{ $value }}"
                            {{ $preferencias['dark_theme_style'] === $value ? 'checked' : '' }}
                        >

                        <span class="theme-style-content">
                            <span class="theme-preview {{ $style['class'] }}">

                                <span class="theme-preview-topbar">
                                    <span class="theme-preview-brand">
                                        <span class="theme-preview-brand-icon">
                                            <i class="fa-solid fa-heart-pulse"></i>
                                        </span>

                                        <span class="theme-preview-brand-line"></span>
                                    </span>

                                    <span class="theme-preview-search">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <span></span>
                                    </span>

                                    <span class="theme-preview-topbar-actions">
                                        <span class="theme-preview-topbar-icon">
                                            <i class="fa-solid fa-house"></i>
                                        </span>

                                        <span class="theme-preview-toggle {{ $style['toggle'] }}">
                                            <span class="theme-preview-toggle-icon theme-preview-toggle-sun">
                                                <i class="fa-solid fa-sun"></i>
                                            </span>

                                            <span class="theme-preview-toggle-icon theme-preview-toggle-moon">
                                                <i class="fa-solid fa-moon"></i>
                                            </span>

                                            <span class="theme-preview-toggle-thumb"></span>
                                        </span>

                                        <span class="theme-preview-topbar-icon">
                                            <i class="fa-solid fa-bell"></i>
                                        </span>

                                        <span class="theme-preview-avatar">C</span>
                                    </span>
                                </span>

                                <span class="theme-preview-sidebar">
                                    <span class="theme-preview-sidebar-section">
                                        Principal
                                    </span>

                                    <span class="theme-preview-sidebar-link active">
                                        <i class="fa-solid fa-gauge-high"></i>
                                        <span>Dashboard</span>
                                    </span>

                                    <span class="theme-preview-sidebar-section">
                                        Administración
                                    </span>

                                    <span class="theme-preview-sidebar-link">
                                        <i class="fa-solid fa-users"></i>
                                        <span>Usuarios</span>
                                        <i class="fa-solid fa-chevron-down theme-preview-caret"></i>
                                    </span>

                                    <span class="theme-preview-sidebar-link">
                                        <i class="fa-solid fa-shield-halved"></i>
                                        <span>Roles</span>
                                    </span>

                                    <span class="theme-preview-sidebar-link">
                                        <i class="fa-solid fa-gear"></i>
                                        <span>Configuración</span>
                                    </span>
                                </span>

                                <span class="theme-preview-main">

                                    <span class="theme-preview-main-header">
                                        <span>
                                            <span class="theme-preview-title"></span>
                                            <span class="theme-preview-subtitle"></span>
                                        </span>

                                        <span class="theme-preview-action">
                                            <i class="fa-solid fa-plus"></i>
                                        </span>
                                    </span>

                                    <span class="theme-preview-stats">
                                        <span class="theme-preview-stat">
                                            <span class="theme-preview-stat-icon">
                                                <i class="fa-solid fa-users"></i>
                                            </span>

                                            <span class="theme-preview-stat-content">
                                                <span></span>
                                                <strong></strong>
                                            </span>
                                        </span>

                                        <span class="theme-preview-stat">
                                            <span class="theme-preview-stat-icon">
                                                <i class="fa-solid fa-calendar-check"></i>
                                            </span>

                                            <span class="theme-preview-stat-content">
                                                <span></span>
                                                <strong></strong>
                                            </span>
                                        </span>

                                        <span class="theme-preview-stat">
                                            <span class="theme-preview-stat-icon">
                                                <i class="fa-solid fa-chart-line"></i>
                                            </span>

                                            <span class="theme-preview-stat-content">
                                                <span></span>
                                                <strong></strong>
                                            </span>
                                        </span>
                                    </span>

                                    <span class="theme-preview-panel">
                                        <span class="theme-preview-panel-header">
                                            <span></span>
                                            <span></span>
                                        </span>

                                        <span class="theme-preview-chart">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </span>
                                    </span>

                                </span>

                            </span>

                            <span class="theme-style-info">
                                <span class="theme-style-info-text">
                                    <strong>{{ $style['name'] }}</strong>
                                    <small>{{ $style['description'] }}</small>
                                </span>

                                <span class="theme-style-check">
                                    <i class="fa-solid fa-check"></i>
                                </span>
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

    </div>
</div>

@endsection