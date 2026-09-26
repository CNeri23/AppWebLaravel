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
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
</head>

<body>
    <div id="toastStack" class="toast-stack"></div>

    <div class="login-page">
        <div class="login-visual">
            <div class="visual-dots"></div>
            <div class="visual-frame"></div>

            <div class="visual-content">
                <div class="visual-brand">
                    <span class="visual-brand-icon"><i class="fa-solid fa-layer-group"></i></span>
                    <span class="visual-brand-name">Admin Panel</span>
                </div>

                <h2 class="visual-title">Todo tu sistema,<br>en un solo lugar.</h2>
                <p class="visual-subtitle">
                    Usuarios, roles, permisos y auditoría, administrados
                    desde un mismo panel.
                </p>
            </div>
        </div>

        {{-- Panel del formulario --}}
        <div class="login-form-side">
            <div class="login-form-wrap">
                <h1 class="login-title">Iniciar sesión</h1>
                <p class="login-subtitle">Ingresa tus credenciales para acceder al panel.</p>

                <form method="POST" action="{{ route('login') }}" id="formLogin" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>

                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>

                            <input type="email" class="form-control" id="email" name="email"
                                value="{{ old('email') }}" placeholder="tucorreo@empresa.com" required autofocus>
                        </div>

                        <div class="invalid-feedback d-block" id="email-error"></div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>

                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>

                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="••••••••" required>

                            <button class="input-group-text toggle-password" type="button" id="togglePassword"
                                tabindex="-1" aria-label="Mostrar contraseña">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>

                        <div class="invalid-feedback d-block" id="password-error"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">Recordarme</label>
                        </div>

                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="link-muted small">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-login w-100">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>
                        Iniciar sesión
                    </button>
                </form>

                <p class="login-footer">© {{ date('Y') }} Todos los derechos reservados.</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/toast.js') }}"></script>
    <script src="{{ asset('js/login.js') }}"></script>
    <script src="{{ asset('js/layout.js') }}"></script>
</body>

</html>