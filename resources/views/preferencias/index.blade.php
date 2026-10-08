@extends('layouts.app')

@section('title', 'Preferencias')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/preferences.css') }}">
@endpush

@section('content')
    <div class="preferences-page">
        <div class="preferences-header">
            <div>
                <span class="preferences-eyebrow">
                    <i class="fa-solid fa-sliders"></i>
                    Personalización
                </span>

                <h1 class="preferences-title">
                    Preferencias
                </h1>

                <p class="preferences-description">
                    Personaliza la apariencia de IronPulse para tu cuenta.
                </p>
            </div>
        </div>

        <section class="preferences-section">
            <div class="preferences-section-header">
                <div>
                    <h2>
                        <i class="fa-solid fa-palette"></i>
                        Apariencia
                    </h2>

                    <p>
                        Elige una variante para cada modo. Tus preferencias son personales y se conservan en tu cuenta.
                    </p>
                </div>

                <div class="preferences-current">
                    <span>Modo actual</span>
                    <strong id="preferencesCurrentMode">
                        {{ $preferencias['theme_mode'] === 'dark' ? 'Oscuro' : 'Claro' }}
                    </strong>
                </div>
            </div>

            <div class="preferences-group">
                <div class="preferences-group-title">
                    <span>
                        <i class="fa-regular fa-sun"></i>
                        Claro
                    </span>

                    <small>
                        {{ ucfirst($preferencias['light_theme_style']) }}
                    </small>
                </div>

                <div class="theme-options">
                    @foreach ([
                        'white' => [
                            'name' => 'White',
                            'description' => 'Claro y limpio',
                        ],
                        'mist' => [
                            'name' => 'Mist',
                            'description' => 'Gris suave',
                        ],
                        'sky' => [
                            'name' => 'Sky',
                            'description' => 'Azul muy sutil',
                        ],
                    ] as $style => $theme)
                        <button type="button"
                            class="theme-option {{ $preferencias['light_theme_style'] === $style ? 'is-selected' : '' }}"
                            data-theme-style-option="{{ $style }}"
                            data-theme-mode-option="light"
                            aria-pressed="{{ $preferencias['light_theme_style'] === $style ? 'true' : 'false' }}">
                            <span class="theme-preview theme-preview-light theme-preview-{{ $style }}">
                                <span class="theme-preview-sidebar"></span>
                                <span class="theme-preview-main">
                                    <span class="theme-preview-topbar"></span>
                                    <span class="theme-preview-card"></span>
                                    <span class="theme-preview-card theme-preview-card-small"></span>
                                </span>
                            </span>

                            <span class="theme-option-info">
                                <strong>{{ $theme['name'] }}</strong>
                                <small>{{ $theme['description'] }}</small>
                            </span>

                            <span class="theme-option-check">
                                <i class="fa-solid fa-check"></i>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="preferences-group">
                <div class="preferences-group-title">
                    <span>
                        <i class="fa-regular fa-moon"></i>
                        Oscuro
                    </span>

                    <small>
                        {{ ucfirst($preferencias['dark_theme_style']) }}
                    </small>
                </div>

                <div class="theme-options">
                    @foreach ([
                        'graphite' => [
                            'name' => 'Graphite',
                            'description' => 'Gris profundo',
                        ],
                        'charcoal' => [
                            'name' => 'Charcoal',
                            'description' => 'Contraste equilibrado',
                        ],
                        'black' => [
                            'name' => 'Black',
                            'description' => 'Negro profundo',
                        ],
                    ] as $style => $theme)
                        <button type="button"
                            class="theme-option {{ $preferencias['dark_theme_style'] === $style ? 'is-selected' : '' }}"
                            data-theme-style-option="{{ $style }}"
                            data-theme-mode-option="dark"
                            aria-pressed="{{ $preferencias['dark_theme_style'] === $style ? 'true' : 'false' }}">
                            <span class="theme-preview theme-preview-dark theme-preview-{{ $style }}">
                                <span class="theme-preview-sidebar"></span>
                                <span class="theme-preview-main">
                                    <span class="theme-preview-topbar"></span>
                                    <span class="theme-preview-card"></span>
                                    <span class="theme-preview-card theme-preview-card-small"></span>
                                </span>
                            </span>

                            <span class="theme-option-info">
                                <strong>{{ $theme['name'] }}</strong>
                                <small>{{ $theme['description'] }}</small>
                            </span>

                            <span class="theme-option-check">
                                <i class="fa-solid fa-check"></i>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/preferences.js') }}"></script>
@endpush
