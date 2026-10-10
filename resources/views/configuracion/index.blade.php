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
                            <input type="text" id="system_name" name="system_name" class="form-control"
                                value="{{ $settings['system_name'] }}" maxlength="100" required>

                            <small>
                                Este nombre se utilizará en las diferentes áreas del sistema.
                            </small>
                        </div>

                        <div class="config-field">
                            <label for="logo">Logotipo</label>

                            <div class="logo-upload">
                                <div class="logo-preview" id="logoPreview">
                                    @if (!empty($settings['logo_path']))
                                        <img src="{{ asset('storage/' . $settings['logo_path']) }}" alt="Logotipo">
                                    @else
                                        <i class="fa-solid fa-image"></i>
                                    @endif
                                </div>

                                <div class="logo-upload-content">
                                    {{-- El texto del input nativo depende del idioma del navegador, por eso se usa un
                                    selector propio --}}
                                    <div class="file-picker">
                                        <input type="file" id="logo" name="logo" class="file-picker-input"
                                            accept="image/png,image/jpeg,image/webp">

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
                                            <span class="accent-dot" style="--accent-preview: {{ $accent['color'] }}"></span>

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

        <div class="configuracion-extra">
            <div id="formNegocio" class="config-card config-card-flex" data-autosave>
                <div class="config-card-header">
                    <div class="config-card-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>

                    <div>
                        <h2>Datos del negocio</h2>
                        <p>Información fiscal y de contacto.</p>
                    </div>
                </div>

                <div class="config-card-body">
                    <div class="config-form-grid">
                        <div class="config-field config-field-full">
                            <label for="business_name">Razón social o nombre comercial</label>
                            <div class="config-input"><input type="text" id="business_name" name="business_name"
                                    class="form-control" value="{{ $settings['business_name'] }}" maxlength="150"><button
                                    type="button" class="config-input-save" hidden aria-label="Guardar cambio"
                                    title="Guardar (Enter)"><i class="fa-solid fa-check"></i></button></div>
                        </div>

                        <div class="config-field">
                            <label for="business_rfc">RFC</label>
                            <div class="config-input"><input type="text" id="business_rfc" name="business_rfc"
                                    class="form-control" value="{{ $settings['business_rfc'] }}" maxlength="13"
                                    placeholder="XAXX010101000"><button type="button" class="config-input-save" hidden
                                    aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                        </div>

                        <div class="config-field">
                            <label for="business_phone">Teléfono</label>
                            <div class="config-input"><input type="tel" id="business_phone" name="business_phone"
                                    class="form-control" value="{{ $settings['business_phone'] }}" maxlength="30"
                                    placeholder="55 1234 5678"><button type="button" class="config-input-save" hidden
                                    aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                        </div>

                        <div class="config-field">
                            <label for="business_email">Correo de contacto</label>
                            <div class="config-input"><input type="email" id="business_email" name="business_email"
                                    class="form-control" value="{{ $settings['business_email'] }}" maxlength="255"
                                    placeholder="contacto@tunegocio.com"><button type="button" class="config-input-save"
                                    hidden aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                        </div>

                        <div class="config-field">
                            <label for="business_website">Sitio web</label>
                            <div class="config-input"><input type="url" id="business_website" name="business_website"
                                    class="form-control" value="{{ $settings['business_website'] }}" maxlength="255"
                                    placeholder="https://tunegocio.com"><button type="button" class="config-input-save"
                                    hidden aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                        </div>

                        <div class="config-field config-field-full">
                            <label for="business_address">Dirección</label>
                            <div class="config-input"><input type="text" id="business_address" name="business_address"
                                    class="form-control" value="{{ $settings['business_address'] }}" maxlength="255"><button
                                    type="button" class="config-input-save" hidden aria-label="Guardar cambio"
                                    title="Guardar (Enter)"><i class="fa-solid fa-check"></i></button></div>
                        </div>

                        <div class="config-field config-field-full">
                            <label for="business_schedule">Horario de atención</label>
                            <div class="config-input config-input-area"><textarea id="business_schedule"
                                    name="business_schedule" class="form-control" rows="3" maxlength="500"
                                    placeholder="Lun a Vie 6:00 – 22:00 · Sáb 8:00 – 14:00">{{ $settings['business_schedule'] }}</textarea><button
                                    type="button" class="config-input-save" hidden aria-label="Guardar cambio"
                                    title="Guardar (Enter)"><i class="fa-solid fa-check"></i></button></div>
                        </div>
                    </div>
                </div>

            </div>

            <div id="formSeguridad" class="config-card config-card-flex" data-autosave>
                <div class="config-card-header">
                    <div class="config-card-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <div>
                        <h2>Seguridad</h2>
                        <p>Acceso, sesiones y contraseñas para todos los usuarios.</p>
                    </div>
                </div>

                <div class="config-card-body">
                    <div class="config-form-grid">
                        <div class="config-field">
                            <label for="session_timeout">Cierre de sesión por inactividad (minutos)</label>
                            <div class="config-input"><input type="number" id="session_timeout" name="session_timeout"
                                    class="form-control" value="{{ $settings['session_timeout'] }}" min="0" max="1440"
                                    step="1" inputmode="numeric"><button type="button" class="config-input-save" hidden
                                    aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                            <small>0 = desactivado. Mínimo 5 minutos.</small>
                        </div>

                        <div class="config-field">
                            <label for="password_min_length">Longitud mínima de contraseña</label>
                            <div class="config-input"><input type="number" id="password_min_length"
                                    name="password_min_length" class="form-control"
                                    value="{{ $settings['password_min_length'] }}" min="8" max="32" step="1"
                                    inputmode="numeric"><button type="button" class="config-input-save" hidden
                                    aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                            <small>Entre 8 y 32 caracteres.</small>
                        </div>

                        <div class="config-field">
                            <label for="max_login_attempts">Intentos fallidos permitidos</label>
                            <div class="config-input"><input type="number" id="max_login_attempts" name="max_login_attempts"
                                    class="form-control" value="{{ $settings['max_login_attempts'] }}" min="3" max="10"
                                    step="1" inputmode="numeric"><button type="button" class="config-input-save" hidden
                                    aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                            <small>Entre 3 y 10 antes de bloquear el acceso.</small>
                        </div>

                        <div class="config-field">
                            <label for="lockout_minutes">Minutos de bloqueo</label>
                            <div class="config-input"><input type="number" id="lockout_minutes" name="lockout_minutes"
                                    class="form-control" value="{{ $settings['lockout_minutes'] }}" min="1" max="60"
                                    step="1" inputmode="numeric"><button type="button" class="config-input-save" hidden
                                    aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                            <small>Tiempo de espera tras superar los intentos.</small>
                        </div>

                        <div class="config-field">
                            <label for="max_login_attempts_ip">Intentos fallidos máximos por IP</label>
                            <div class="config-input"><input type="number" id="max_login_attempts_ip" name="max_login_attempts_ip"
                                    class="form-control" value="{{ $settings['max_login_attempts_ip'] }}" min="5" max="1000"
                                    step="1" inputmode="numeric"><button type="button" class="config-input-save" hidden
                                    aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                            <small>Entre 5 y 1000 intentos fallidos desde una misma IP.</small>
                        </div>

                        <div class="config-field">
                            <label for="login_ip_window_seconds">Ventana del límite por IP (segundos)</label>
                            <div class="config-input"><input type="number" id="login_ip_window_seconds" name="login_ip_window_seconds"
                                    class="form-control" value="{{ $settings['login_ip_window_seconds'] }}" min="10" max="3600"
                                    step="1" inputmode="numeric"><button type="button" class="config-input-save" hidden
                                    aria-label="Guardar cambio" title="Guardar (Enter)"><i
                                        class="fa-solid fa-check"></i></button></div>
                            <small>Entre 10 y 3600 segundos (1 minuto a 1 hora).</small>
                        </div>

                        <div class="config-field config-field-full config-switch">
                            <div class="config-switch-text">
                                <label for="password_complexity">Exigir contraseñas robustas</label>
                                <small>Debe incluir mayúsculas, minúsculas y números.</small>
                            </div>

                            <div class="form-check form-switch">
                                <input type="hidden" name="password_complexity" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="password_complexity"
                                    name="password_complexity" value="1" {{ $settings['password_complexity'] ? 'checked' : '' }}>
                            </div>
                        </div>

                        <div class="config-field config-field-full config-switch">
                            <div class="config-switch-text">
                                <label for="registration_enabled">Permitir registro público</label>
                                <small>Si se desactiva, nadie podrá crear una cuenta desde la pantalla de inicio de
                                    sesión.</small>
                            </div>

                            <div class="form-check form-switch">
                                <input type="hidden" name="registration_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="registration_enabled"
                                    name="registration_enabled" value="1" {{ $settings['registration_enabled'] ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection