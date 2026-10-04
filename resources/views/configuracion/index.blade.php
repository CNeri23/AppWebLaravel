@extends('layouts.app')
@section('content')

<div class="container-fluid py-4 configuracion-page">
    <form id="formConfiguracion" enctype="multipart/form-data">
        <div class="configuracion-grid">
            <section class="config-card">
                <div class="config-card-header">
                    <div class="config-card-icon">
                        <i class="fa-solid fa-gear"></i>
                    </div>

                    <div>
                        <h2>General</h2>
                        <p>Información principal del sistema.</p>
                    </div>
                </div>

                <div class="config-card-body">
                    <div class="config-field">
                        <label for="system_name">Nombre del sistema</label>
                        <input type="text" id="system_name" name="system_name" class="form-control" value="{{ $settings['system_name'] }}" maxlength="100" required>

                        <small>
                            Este nombre se utilizará en las diferentes áreas del sistema.
                        </small>
                    </div>

                    <div class="config-field">
                        <label for="logo">Logotipo</label>

                        <div class="logo-upload">
                            <div class="logo-preview" id="logoPreview">
                                @if (!empty($settings['logo_path']))
                                    <img src="{{ asset('storage/' . $settings['logo_path']) }}"  alt="Logotipo">
                                @else
                                    <i class="fa-solid fa-image"></i>
                                @endif
                            </div>

                            <div class="logo-upload-content">
                                {{-- El texto del input nativo depende del idioma del navegador, por eso se usa un selector propio --}}
                                <div class="file-picker">
                                    <input type="file" id="logo" name="logo" class="file-picker-input" accept="image/png,image/jpeg,image/webp">

                                    <label for="logo" class="file-picker-button">Elegir archivo</label>

                                    <span class="file-picker-name" id="logoNombre">Ningún archivo seleccionado</span>
                                </div>

                                <small>
                                    PNG, JPG, JPEG o WEBP. Máximo 2 MB.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="config-card">

                <div class="config-card-header">
                    <div class="config-card-icon">
                        <i class="fa-solid fa-palette"></i>
                    </div>

                    <div>
                        <h2>Apariencia</h2>
                        <p>Personaliza la apariencia visual del sistema.</p>
                    </div>
                </div>

                <div class="config-card-body">
                    <div class="config-field">
                        <label>Modo del tema</label>

                        <div class="theme-mode-grid">
                            <label class="theme-mode-option">
                                <input type="radio" name="theme_mode" value="light" {{ ($settings['theme_mode'] ?? 'light') !== 'dark' ? 'checked' : '' }}>

                                <span class="theme-mode-content">
                                    <i class="fa-solid fa-sun"></i>
                                    <strong>Claro</strong>
                                    <small>Interfaz clara</small>
                                </span>
                            </label>

                            <label class="theme-mode-option">
                                <input type="radio" name="theme_mode" value="dark" {{ $settings['theme_mode'] === 'dark' ? 'checked' : '' }}>

                                <span class="theme-mode-content">
                                    <i class="fa-solid fa-moon"></i>
                                    <strong>Oscuro</strong>
                                    <small>Interfaz oscura</small>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="config-field">
                        <label>Temas claros</label>

                        <small>
                            Selecciona el estilo que utilizará el sistema cuando esté en modo claro.
                        </small>

                        <div class="theme-style-grid">
                            @php
                                $lightThemeStyles = [
                                    'white' => [
                                        'name' => 'White',
                                        'description' => 'Claro limpio',
                                        'class' => 'theme-preview-white',
                                    ],
                                    'mist' => [
                                        'name' => 'Mist',
                                        'description' => 'Claro suave',
                                        'class' => 'theme-preview-mist',
                                    ],
                                    'sky' => [
                                        'name' => 'Sky',
                                        'description' => 'Claro fresco',
                                        'class' => 'theme-preview-sky',
                                    ],
                                ];
                            @endphp

                            @foreach ($lightThemeStyles as $value => $style)
                                <label class="theme-style-option">
                                    <input type="radio" name="light_theme_style" value="{{ $value }}" {{ ($settings['light_theme_style'] ?? 'white') === $value ? 'checked' : '' }}>

                                    <span class="theme-style-content">
                                        <span class="theme-preview {{ $style['class'] }}">
                                            <span class="theme-preview-sidebar"></span>
                                            <span class="theme-preview-main">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </span>
                                        </span>

                                        <span class="theme-style-info">
                                            <strong>{{ $style['name'] }}</strong>
                                            <small>{{ $style['description'] }}</small>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="config-field">
                        <label>Temas oscuros</label>

                        <small>
                            Selecciona el estilo que utilizará el sistema cuando esté en modo oscuro.
                        </small>

                        <div class="theme-style-grid">
                            @php
                                $darkThemeStyles = [
                                    'graphite' => [
                                        'name' => 'Graphite',
                                        'description' => 'Oscuro elegante',
                                        'class' => 'theme-preview-graphite',
                                    ],
                                    'charcoal' => [
                                        'name' => 'Charcoal',
                                        'description' => 'Oscuro neutro',
                                        'class' => 'theme-preview-charcoal',
                                    ],
                                    'black' => [
                                        'name' => 'Black',
                                        'description' => 'Negro profundo',
                                        'class' => 'theme-preview-black',
                                    ],
                                ];
                            @endphp

                            @foreach ($darkThemeStyles as $value => $style)
                                <label class="theme-style-option">
                                    <input type="radio" name="dark_theme_style" value="{{ $value }}" {{ ($settings['dark_theme_style'] ?? 'graphite') === $value ? 'checked' : '' }}>

                                    <span class="theme-style-content">
                                        <span class="theme-preview {{ $style['class'] }}">
                                            <span class="theme-preview-sidebar"></span>
                                            <span class="theme-preview-main">
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </span>
                                        </span>

                                        <span class="theme-style-info">
                                            <strong>{{ $style['name'] }}</strong>
                                            <small>{{ $style['description'] }}</small>
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="config-field">
                        <label>Color de acento</label>

                        <div class="accent-grid">
                            @php
                                $accentColors = [
                                    'blue' => ['name' => 'Azul', 'color' => '#007aff'],
                                    'green' => ['name' => 'Verde', 'color' => '#34c759'],
                                    'orange' => ['name' => 'Naranja', 'color' => '#ff9500'],
                                    'purple' => ['name' => 'Morado', 'color' => '#af52de'],
                                    'red' => ['name' => 'Rojo', 'color' => '#ff3b30'],
                                    'cyan' => ['name' => 'Cian', 'color' => '#32ade6'],
                                    'neutral' => ['name' => 'Neutro', 'color' => '#8e8e93'],
                                ];
                            @endphp

                            @foreach ($accentColors as $value => $accent)

                                <label class="accent-option">
                                    <input type="radio" name="accent_color" value="{{ $value }}" {{ $settings['accent_color'] === $value ? 'checked' : '' }}>

                                    <span class="accent-content">
                                        <span  class="accent-dot" style="--accent-preview: {{ $accent['color'] }}"></span>

                                        <span>{{ $accent['name'] }}</span>
                                    </span>
                                </label>
                            @endforeach

                        </div>

                        <small>
                            Este color se utilizará como acento principal en módulos, submódulos,
                            estados activos e indicadores.
                        </small>
                    </div>
                </div>
            </section>

            <section class="config-card">

                <div class="config-card-header">
                    <div class="config-card-icon">
                        <i class="fa-solid fa-earth-americas"></i>
                    </div>

                    <div>
                        <h2>Regional</h2>
                        <p>Define formatos y preferencias regionales.</p>
                    </div>
                </div>

                <div class="config-card-body">
                    <div class="regional-grid">
                        <div class="config-field">
                            <label for="currency">Moneda</label>

                            <select id="currency" name="currency" class="form-select">
                                <option value="MXN" {{ $settings['currency'] === 'MXN' ? 'selected' : '' }}>
                                    MXN — Peso mexicano
                                </option>

                                <option value="USD" {{ $settings['currency'] === 'USD' ? 'selected' : '' }}>
                                    USD — Dólar estadounidense
                                </option>

                                <option value="EUR" {{ $settings['currency'] === 'EUR' ? 'selected' : '' }}>
                                    EUR — Euro
                                </option>
                            </select>
                        </div>

                        <div class="config-field">
                            <label for="timezone">Zona horaria</label>

                            <select id="timezone" name="timezone" class="form-select">
                                <option value="America/Mexico_City" {{ $settings['timezone'] === 'America/Mexico_City' ? 'selected' : '' }}>
                                    Ciudad de México
                                </option>

                                <option value="America/Monterrey" {{ $settings['timezone'] === 'America/Monterrey' ? 'selected' : '' }}>
                                    Monterrey
                                </option>

                                <option value="America/Tijuana" {{ $settings['timezone'] === 'America/Tijuana' ? 'selected' : '' }}>
                                    Tijuana
                                </option>

                                <option value="America/Cancun" {{ $settings['timezone'] === 'America/Cancun' ? 'selected' : '' }}>
                                    Cancún
                                </option>

                                <option value="America/New_York" {{ $settings['timezone'] === 'America/New_York' ? 'selected' : '' }}>
                                    Nueva York
                                </option>

                                <option value="America/Los_Angeles" {{ $settings['timezone'] === 'America/Los_Angeles' ? 'selected' : '' }}>
                                    Los Ángeles
                                </option>

                                <option value="Europe/Madrid" {{ $settings['timezone'] === 'Europe/Madrid' ? 'selected' : '' }}>
                                    Madrid
                                </option>
                            </select>
                        </div>

                        <div class="config-field">
                            <label for="date_format">Formato de fecha</label>

                            <select id="date_format" name="date_format" class="form-select">
                                <option value="d/m/Y" {{ $settings['date_format'] === 'd/m/Y' ? 'selected' : '' }}>
                                    31/12/2026
                                </option>

                                <option value="m/d/Y" {{ $settings['date_format'] === 'm/d/Y' ? 'selected' : '' }}>
                                    12/31/2026
                                </option>

                                <option value="Y-m-d" {{ $settings['date_format'] === 'Y-m-d' ? 'selected' : '' }}>
                                    2026-12-31
                                </option>
                            </select>
                        </div>

                        <div class="config-field">
                            <label for="time_format">Formato de hora</label>
                            <select id="time_format" name="time_format" class="form-select">
                                <option value="H:i" {{ $settings['time_format'] === 'H:i' ? 'selected' : '' }}>
                                    14:30
                                </option>

                                <option value="h:i A" {{ $settings['time_format'] === 'h:i A' ? 'selected' : '' }}>
                                    02:30 PM
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </form>
</div>

@endsection