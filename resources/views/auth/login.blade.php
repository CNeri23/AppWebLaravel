@php
    $systemSettings = app(\App\Services\SystemSettings::class)->all();

    // Antes de autenticar no existe una preferencia de tema asociada a un usuario.
    // El login utiliza un tema inicial temporal; después del acceso se aplica la preferencia personal.
    $themeMode = 'light';
    $lightThemeStyle = 'white';
    $darkThemeStyle = 'graphite';
    $accentColor = $systemSettings['accent_color'] ?? 'orange';
    $systemName = $systemSettings['system_name'] ?? 'IronPulse';

    $registroHabilitado = $registroHabilitado
        ?? (bool) ($systemSettings['registration_enabled'] ?? true);
    $passwordMinimo = $passwordMinimo ?? (int) ($systemSettings['password_min_length'] ?? 8);
    $passwordComplejo = $passwordComplejo ?? (bool) ($systemSettings['password_complexity'] ?? false);
    $passwordDescripcion = $passwordDescripcion
        ?? ('Mínimo ' . $passwordMinimo . ' caracteres'
            . ($passwordComplejo ? ', con mayúsculas, minúsculas y números' : '') . '.');

    $themeStyles = [
        'white',
        'mist',
        'sky',
        'graphite',
        'charcoal',
        'black',
    ];

    if (!in_array($lightThemeStyle, ['white', 'mist', 'sky'])) {
        $lightThemeStyle = 'white';
    }

    if (!in_array($darkThemeStyle, ['graphite', 'charcoal', 'black'])) {
        $darkThemeStyle = 'graphite';
    }

    if (!in_array($accentColor, [
        'blue',
        'green',
        'orange',
        'purple',
        'red',
        'cyan',
        'neutral',
    ])) {
        $accentColor = 'orange';
    }
@endphp

<!DOCTYPE html>
<html
    lang="es"
    data-bs-theme="{{ $themeMode === 'dark' ? 'dark' : 'light' }}"
    data-theme-mode="{{ $themeMode }}"
    data-light-theme-style="{{ $lightThemeStyle }}"
    data-dark-theme-style="{{ $darkThemeStyle }}"
    data-theme-style="{{ $themeMode === 'dark' ? $darkThemeStyle : $lightThemeStyle }}"
    data-accent-color="{{ $accentColor }}"
    data-system-name="{{ $systemName }}"
    data-theme-guest="true"
>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $systemName }}</title>
    <script src="{{ asset('js/layout.js') }}"></script>

    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">

    @vite(['resources/js/toast.js'])
</head>

<body>
    <div id="toastStack" class="toast-stack"></div>
    <div class="login-page">
        <div class="login-form-side">
            <div class="login-form-wrap">
                <div class="auth-form-stage" id="authFormStage" data-initial-panel="{{ $authPanel ?? 'login' }}"
                    data-registration-enabled="{{ $registroHabilitado ? '1' : '0' }}"
                    data-password-min="{{ $passwordMinimo }}"
                    data-password-complex="{{ $passwordComplejo ? '1' : '0' }}">
                    <div class="auth-panel auth-panel-login" data-panel="login">
                        <h1 class="login-title text-center">Iniciar sesión</h1>

                        <p class="login-subtitle">
                            Ingresa tus credenciales para acceder al sistema.
                        </p>

                        <form method="POST" action="{{ route('login.submit') }}" id="formLogin" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    Usuario
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-user"></i>
                                    </span>

                                    <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="Ingresa tu usuario">
                                </div>

                                <div class="invalid-feedback d-block" id="username-error"></div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Contraseña
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>

                                    <input type="password" class="form-control" id="password" name="password" required placeholder="Ingresa tu contraseña">

                                    <button class="input-group-text toggle-password" type="button" data-password-target="password" tabindex="-1" aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

                                <div class="invalid-feedback d-block" id="password-error"></div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">

                                    <label class="form-check-label" for="remember">
                                        Recordarme
                                    </label>
                                </div>

                                <button type="button" class="auth-link link-muted" data-auth-target="forgot">
                                    ¿Olvidaste tu contraseña?
                                </button>
                            </div>

                            <button type="submit" class="btn btn-login w-100">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                Iniciar sesión
                            </button>
                        </form>

                        @if ($registroHabilitado)
                        <div class="auth-switch">
                            <span>¿No tienes una cuenta?</span>

                            <button type="button" class="auth-link" data-auth-target="register">
                                Regístrate
                            </button>
                        </div>
                        @endif

                        <p class="login-footer">
                            © {{ date('Y') }} Todos los derechos reservados.
                        </p>
                    </div>

                    @if ($registroHabilitado)
                    <div class="auth-panel auth-panel-register" data-panel="register">

                        <h1 class="login-title text-center">
                            Crear cuenta
                        </h1>

                        <p class="login-subtitle">
                            Regístrate para crear tu cuenta en el sistema.
                        </p>

                        <form method="POST" action="{{ route('register') }}" id="formRegister" novalidate>
                            @csrf

                            <div class="mb-3">
                                <label for="registerUsername" class="form-label">
                                    Usuario
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-user"></i>
                                    </span>

                                    <input type="text" class="form-control" id="registerUsername" name="username" required placeholder="Elige tu usuario para iniciar sesión" maxlength="50" autocomplete="username" autocapitalize="none" spellcheck="false">
                                </div>

                                <div class="invalid-feedback d-block" id="register-username-error"></div>
                            </div>

                            <div class="mb-3">
                                <label for="registerNombre" class="form-label">
                                    Nombre(s)
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-id-card"></i>
                                    </span>

                                    <input type="text" class="form-control" id="registerNombre" name="nombre" required placeholder="Ingresa tu nombre" maxlength="100" autocomplete="given-name">
                                </div>

                                <div class="invalid-feedback d-block" id="register-nombre-error"></div>
                            </div>

                            <div class="row gx-3">
                                <div class="col-12 col-sm-6 mb-3">
                                    <label for="registerApellidoPaterno" class="form-label">
                                        Apellido paterno
                                    </label>

                                    <div class="input-group has-validation">
                                        <span class="input-group-text">
                                            <i class="fa-solid fa-id-card"></i>
                                        </span>

                                        <input type="text" class="form-control" id="registerApellidoPaterno" name="apellido_paterno" required placeholder="Apellido paterno" maxlength="100" autocomplete="family-name">
                                    </div>

                                    <div class="invalid-feedback d-block" id="register-apellido-paterno-error"></div>
                                </div>
                                <div class="col-12 col-sm-6 mb-3">
                                    <label for="registerApellidoMaterno" class="form-label">
                                        Apellido materno (opc.)
                                    </label>

                                    <div class="input-group has-validation">
                                        <span class="input-group-text">
                                            <i class="fa-solid fa-id-card"></i>
                                        </span>

                                        <input type="text" class="form-control" id="registerApellidoMaterno" name="apellido_materno" placeholder="Apellido materno" maxlength="100">
                                    </div>

                                    <div class="invalid-feedback d-block" id="register-apellido-materno-error"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="registerTelefono" class="form-label">
                                    Teléfono (opcional)
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-phone"></i>
                                    </span>

                                    <input type="tel" class="form-control" id="registerTelefono" name="telefono" placeholder="Ingresa tu teléfono" maxlength="30" autocomplete="tel">
                                </div>

                                <div class="invalid-feedback d-block" id="register-telefono-error"></div>
                            </div>

                            <div class="mb-3">
                                <label for="registerEmail" class="form-label">
                                    Correo electrónico
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>

                                    <input type="email" class="form-control" id="registerEmail" name="email" required placeholder="example@domain.com" maxlength="255" autocomplete="email">
                                </div>

                                <div class="invalid-feedback d-block" id="register-email-error"></div>
                            </div>

                            <div class="mb-3">
                                <label for="registerPassword" class="form-label">
                                    Contraseña
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>

                                    <input type="password" class="form-control" id="registerPassword" name="password" required placeholder="Ingresa tu contraseña">

                                    <button class="input-group-text toggle-password" type="button"
                                        data-password-target="registerPassword" tabindex="-1"
                                        aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

                                <div class="auth-password-hint" id="register-password-hint">{{ $passwordDescripcion }}</div>

                                <div class="invalid-feedback d-block" id="register-password-error"></div>
                            </div>

                            <div class="mb-4">
                                <label for="registerPasswordConfirmation" class="form-label">
                                    Confirmar contraseña
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>

                                    <input type="password" class="form-control" id="registerPasswordConfirmation" name="password_confirmation" required placeholder="Confirma tu contraseña">

                                    <button class="input-group-text toggle-password" type="button"
                                        data-password-target="registerPasswordConfirmation" tabindex="-1"
                                        aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

                                <div class="invalid-feedback d-block" id="register-password-confirmation-error"></div>
                            </div>

                            <button type="submit" class="btn btn-login w-100">
                                <i class="fa-solid fa-user-plus"></i>
                                Crear cuenta
                            </button>
                        </form>

                        <div class="auth-switch">
                            <span>¿Ya tienes una cuenta?</span>

                            <button type="button" class="auth-link" data-auth-target="login">
                                Iniciar sesión
                            </button>
                        </div>

                        <p class="login-footer">
                            © {{ date('Y') }} Todos los derechos reservados.
                        </p>
                    </div>
                    @endif

                    <div class="auth-panel auth-panel-forgot" data-panel="forgot">

                        <h1 class="login-title text-center">
                            Recuperar contraseña
                        </h1>

                        <p class="login-subtitle">
                            Ingresa tu correo y te enviaremos instrucciones para restablecer tu contraseña.
                        </p>

                        <form method="POST" action="{{ route('password.email') }}" id="formForgot" novalidate>
                            @csrf

                            <div class="mb-4">
                                <label for="forgotEmail" class="form-label">
                                    Correo electrónico
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>

                                    <input type="email" class="form-control" id="forgotEmail" name="email" required placeholder="example@domain.com">
                                </div>

                                <div class="invalid-feedback d-block" id="forgot-email-error"></div>
                            </div>

                            <button type="submit" class="btn btn-login w-100">
                                <i class="fa-solid fa-paper-plane"></i>
                                Enviar
                            </button>
                        </form>

                        <div class="auth-switch">
                            <button type="button" class="auth-link" data-auth-target="login">
                                <i class="fa-solid fa-arrow-left me-1"></i>
                                Volver a iniciar sesión
                            </button>
                        </div>

                        <p class="login-footer">
                            © {{ date('Y') }} Todos los derechos reservados.
                        </p>
                    </div>

                    <div class="auth-panel auth-panel-reset" data-panel="reset">

                        <h1 class="login-title text-center">
                            Restablecer contraseña
                        </h1>

                        <p class="login-subtitle">
                            Ingresa tu nueva contraseña para recuperar el acceso a tu cuenta.
                        </p>

                        <form method="POST" action="{{ route('password.update') }}" id="formResetPassword" novalidate>
                            @csrf

                            <input type="hidden" name="token" id="resetToken" value="{{ $token ?? '' }}">

                            <div class="mb-3">
                                <label for="resetEmail" class="form-label">
                                    Correo electrónico
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>

                                    <input type="email" class="form-control" id="resetEmail" name="email"
                                        value="{{ $email ?? '' }}" required readonly>
                                </div>

                                <div class="invalid-feedback d-block" id="reset-email-error"></div>
                            </div>

                            <div class="mb-3">
                                <label for="resetPassword" class="form-label">
                                    Nueva contraseña
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>

                                    <input type="password" class="form-control" id="resetPassword" name="password" required placeholder="Ingresa tu nueva contraseña">

                                    <button class="input-group-text toggle-password" type="button"
                                        data-password-target="resetPassword" tabindex="-1"
                                        aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

                                <div class="auth-password-hint" id="reset-password-hint">{{ $passwordDescripcion }}</div>

                                <div class="invalid-feedback d-block" id="reset-password-error"></div>
                            </div>

                            <div class="mb-4">
                                <label for="resetPasswordConfirmation" class="form-label">
                                    Confirmar contraseña
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>

                                    <input type="password" class="form-control" id="resetPasswordConfirmation" name="password_confirmation" required placeholder="Confirma tu nueva contraseña">

                                    <button class="input-group-text toggle-password" type="button"
                                        data-password-target="resetPasswordConfirmation" tabindex="-1"
                                        aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

                                <div class="invalid-feedback d-block" id="reset-password-confirmation-error"></div>
                            </div>

                            <button type="submit" class="btn btn-login w-100">
                                <i class="fa-solid fa-key"></i>
                                Restablecer contraseña
                            </button>
                        </form>

                        <div class="auth-switch">
                            <button type="button" class="auth-link" data-auth-target="login">
                                <i class="fa-solid fa-arrow-left me-1"></i>
                                Volver a iniciar sesión
                            </button>
                        </div>

                        <p class="login-footer">
                            © {{ date('Y') }} Todos los derechos reservados.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-visual">
            <div class="visual-dots"></div>
            <div class="visual-frame"></div>

            <div class="visual-content">
                <div class="visual-brand">
                    <span class="visual-brand-icon">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </span>

                    <span class="visual-brand-name">{{ $systemName }}</span>
                </div>

                <h2 class="visual-title">
                    Todo tu <span class="visual-accent">gimnasio</span>, en un solo lugar.
                </h2>

                <p class="visual-subtitle">
                    Membresías, accesos, clases y pagos, administrados desde un mismo panel.
                </p>

                <span class="visual-ruler" aria-hidden="true"></span>

                <div class="gym-scene" aria-hidden="true">
                    <div class="gym-stats">
                        <div class="gym-chip">
                            <span class="gym-chip-icon"><i class="fa-solid fa-users"></i></span>
                            <span class="gym-chip-text">
                                <span class="gym-chip-label">Miembros</span>
                                <span class="gym-chip-value" data-countup="1248">1,248</span>
                            </span>
                        </div>

                        <div class="gym-chip">
                            <span class="gym-chip-icon"><i class="fa-solid fa-calendar-check"></i></span>
                            <span class="gym-chip-text">
                                <span class="gym-chip-label">Check-ins hoy</span>
                                <span class="gym-chip-value" data-countup="86" data-live>86</span>
                            </span>
                        </div>

                        <div class="gym-chip">
                            <span class="gym-chip-icon"><i class="fa-solid fa-dumbbell"></i></span>
                            <span class="gym-chip-text">
                                <span class="gym-chip-label">Repeticiones</span>
                                <span class="gym-chip-value"><span data-reps>00</span><small>/12</small></span>
                            </span>
                        </div>
                    </div>

                    <div class="gym-stage">
                        <div class="gym-glow"></div>

                        <svg class="gym-svg" viewBox="0 0 460 236" xmlns="http://www.w3.org/2000/svg" role="presentation" focusable="false">
                            <defs>
                                <pattern id="gsKnurl" width="4" height="4" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
                                    <path class="gs-knurl-line" d="M0 0V4" />
                                </pattern>
                            </defs>

                            <!-- Pulso cardíaco en el piso -->
                            <path class="gs-ecg-track" d="M0 212H168L176 206L184 218L196 180L208 232L218 210L224 212H460" />
                            <path class="gs-ecg" pathLength="100" d="M0 212H168L176 206L184 218L196 180L208 232L218 210L224 212H460" />
                            <circle class="gs-pulse-dot" cx="0" cy="0" r="4" />

                            <!-- Sombra en el piso -->
                            <ellipse class="gs-shadow" cx="230" cy="196" rx="150" ry="7" />

                            <!-- Barra con discos -->
                            <g class="gs-drop">
                                <g class="gs-lift">
                                    <rect class="gs-bar" x="20" y="96" width="420" height="8" rx="4" />
                                    <rect class="gs-knurl" x="180" y="95.5" width="100" height="9" />
                                    <rect class="gs-bar-shine" x="20" y="97" width="420" height="2" rx="1" />

                                    <!-- Lado izquierdo -->
                                    <rect class="gs-collar" x="150" y="88" width="8" height="24" rx="2" />
                                    <rect class="gs-plate-main" x="118" y="30" width="28" height="140" rx="7" />
                                    <rect class="gs-plate-shine" x="123" y="38" width="4" height="124" rx="2" />
                                    <rect class="gs-plate-mid" x="92" y="48" width="22" height="104" rx="6" />
                                    <rect class="gs-plate-shine" x="96" y="55" width="3" height="90" rx="1.5" />
                                    <rect class="gs-plate-small" x="70" y="64" width="18" height="72" rx="5" />
                                    <rect class="gs-cap" x="50" y="90" width="16" height="20" rx="3" />

                                    <!-- Lado derecho -->
                                    <rect class="gs-collar" x="302" y="88" width="8" height="24" rx="2" />
                                    <rect class="gs-plate-main" x="314" y="30" width="28" height="140" rx="7" />
                                    <rect class="gs-plate-shine" x="333" y="38" width="4" height="124" rx="2" />
                                    <rect class="gs-plate-mid" x="346" y="48" width="22" height="104" rx="6" />
                                    <rect class="gs-plate-shine" x="361" y="55" width="3" height="90" rx="1.5" />
                                    <rect class="gs-plate-small" x="372" y="64" width="18" height="72" rx="5" />
                                    <rect class="gs-cap" x="394" y="90" width="16" height="20" rx="3" />
                                </g>
                            </g>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/toast.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
</body>

</html>