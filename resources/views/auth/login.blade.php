<!DOCTYPE html>
<html lang="es" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Iniciar sesión</title>

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
                <div class="auth-form-stage" id="authFormStage" data-initial-panel="{{ $authPanel ?? 'login' }}">
                    <div class="auth-panel auth-panel-login" data-panel="login">
                        <h1 class="login-title text-center">Iniciar sesión</h1>

                        <p class="login-subtitle">
                            Ingresa tus credenciales para acceder al sistema.
                        </p>

                        <form method="POST" action="{{ route('login.submit') }}" id="formLogin" novalidate>
                            @csrf
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Correo electrónico
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>

                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" required autofocus>
                                </div>

                                <div class="invalid-feedback d-block" id="email-error"></div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Contraseña
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-lock"></i>
                                    </span>

                                    <input type="password" class="form-control" id="password" name="password" required>

                                    <button class="input-group-text toggle-password" type="button"
                                        data-password-target="password" tabindex="-1" aria-label="Mostrar contraseña">
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

                        <div class="auth-switch">
                            <span>¿No tienes una cuenta?</span>

                            <button type="button" class="auth-link" data-auth-target="register">
                                Regístrate
                            </button>
                        </div>

                        <p class="login-footer">
                            © {{ date('Y') }} Todos los derechos reservados.
                        </p>
                    </div>

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
                                <label for="registerName" class="form-label">
                                    Nombre completo
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-user"></i>
                                    </span>

                                    <input type="text" class="form-control" id="registerName" name="name" required>
                                </div>

                                <div class="invalid-feedback d-block" id="register-name-error"></div>
                            </div>

                            <div class="mb-3">
                                <label for="registerEmail" class="form-label">
                                    Correo electrónico
                                </label>

                                <div class="input-group has-validation">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>

                                    <input type="email" class="form-control" id="registerEmail" name="email" required>
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

                                    <input type="password" class="form-control" id="registerPassword" name="password" required>

                                    <button class="input-group-text toggle-password" type="button"
                                        data-password-target="registerPassword" tabindex="-1"
                                        aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

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

                                    <input type="password" class="form-control" id="registerPasswordConfirmation" name="password_confirmation"  required>

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

                                    <input type="email" class="form-control" id="forgotEmail" name="email" required>
                                </div>

                                <div class="invalid-feedback d-block" id="forgot-email-error"></div>
                            </div>

                            <button type="submit" class="btn btn-login w-100">
                                <i class="fa-solid fa-paper-plane"></i>
                                Enviar instrucciones
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

                                    <input type="password" class="form-control" id="resetPassword" name="password" required>

                                    <button class="input-group-text toggle-password" type="button"
                                        data-password-target="resetPassword" tabindex="-1"
                                        aria-label="Mostrar contraseña">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>

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

                                    <input type="password" class="form-control" id="resetPasswordConfirmation" name="password_confirmation" required>

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
                        <i class="fa-solid fa-layer-group"></i>
                    </span>

                    <span class="visual-brand-name">
                        Admin Panel
                    </span>
                </div>

                <h2 class="visual-title">
                    Todo tu sistema,<br>en un solo lugar.
                </h2>

                <p class="visual-subtitle">
                    Usuarios, roles, permisos y auditoría, administrados desde un mismo panel.
                </p>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/toast.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
    <script src="{{ asset('js/layout.js') }}"></script>
</body>

</html>