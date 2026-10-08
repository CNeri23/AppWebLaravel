<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PasswordPolicy;
use App\Services\SystemSettings;
use App\Services\UsuarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Ventana (en segundos) en la que se cuentan los intentos fallidos. */
    private const VENTANA_INTENTOS = 900;

    /** Datos que comparten las vistas del login y del restablecimiento. */
    private function datosAuth(): array
    {
        $politica = app(PasswordPolicy::class);

        return [
            'registroHabilitado' => (bool) app(SystemSettings::class)
                ->get('registration_enabled', true),
            'passwordMinimo' => $politica->minLength(),
            'passwordComplejo' => $politica->requiresComplexity(),
            'passwordDescripcion' => $politica->description(),
        ];
    }

    public function showLogin(): View
    {
        return view('auth.login', array_merge([
            'token' => null,
            'email' => null,
            'authPanel' => 'login',
        ], $this->datosAuth()));
    }

    public function login(Request $request, SystemSettings $settings)
    {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ], [
            'username.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $maxIntentos = max(1, (int) $settings->get('max_login_attempts', 5));
        $minutosBloqueo = max(1, (int) $settings->get('lockout_minutes', 5));

        $claveIntentos = 'login:' . $credentials['username'] . '|' . $request->ip();
        $claveBloqueo = $claveIntentos . ':bloqueo';

        if (RateLimiter::tooManyAttempts($claveBloqueo, 1)) {
            return $this->respuestaBloqueo(RateLimiter::availableIn($claveBloqueo));
        }

        // Cuenta desactivada: solo se avisa si la contraseña es correcta,
        // para no revelar a un extraño qué usuarios existen.
        $existente = User::where('username', $credentials['username'])->first();

        if (
            $existente
            && !$existente->activo
            && Hash::check($credentials['password'], $existente->password)
        ) {
            AuditLogService::log(
                module: 'autenticacion',
                action: 'LOGIN_INACTIVO',
                description: 'El usuario "' . $existente->username .
                '" intentó iniciar sesión, pero su cuenta está desactivada.',
                entity: $existente
            );

            return response()->json([
                'success' => false,
                'mensaje' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ], 403);
        }

        $intento = [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'activo' => true,
        ];

        if (Auth::attempt($intento, $request->boolean('remember'))) {

            RateLimiter::clear($claveIntentos);

            $request->session()->regenerate();

            $usuario = Auth::user();

            AuditLogService::log(
                module: 'autenticacion',
                action: 'LOGIN',
                description: 'El usuario "' . $usuario->username .
                '" (' . $usuario->name . ') inició sesión.',
                entity: $usuario
            );

            return response()->json([
                'success' => true,
                'mensaje' => 'Inicio de sesión correcto.',
                'redirect' => redirect()->intended('/dashboard')->getTargetUrl(),
            ]);
        }

        AuditLogService::log(
            module: 'autenticacion',
            action: 'LOGIN_FALLIDO',
            description: 'Se intentó iniciar sesión con el usuario "' .
            $credentials['username'] . '", pero las credenciales no fueron correctas.'
        );

        RateLimiter::hit($claveIntentos, self::VENTANA_INTENTOS);

        $intentos = RateLimiter::attempts($claveIntentos);

        if ($intentos >= $maxIntentos) {
            RateLimiter::hit($claveBloqueo, $minutosBloqueo * 60);
            RateLimiter::clear($claveIntentos);

            AuditLogService::log(
                module: 'autenticacion',
                action: 'LOGIN_BLOQUEADO',
                description: 'Se bloqueó temporalmente el acceso del usuario "' .
                $credentials['username'] . '" durante ' . $minutosBloqueo .
                ' min por superar los intentos fallidos permitidos.'
            );

            return $this->respuestaBloqueo(RateLimiter::availableIn($claveBloqueo));
        }

        $restantes = $maxIntentos - $intentos;

        return response()->json([
            'success' => false,
            'mensaje' => 'Las credenciales no son correctas. ' .
                ($restantes === 1
                    ? 'Te queda 1 intento.'
                    : 'Te quedan ' . $restantes . ' intentos.'),
        ], 401);
    }

    private function respuestaBloqueo(int $segundos)
    {
        $minutos = max(1, (int) ceil($segundos / 60));

        return response()->json([
            'success' => false,
            'mensaje' => 'Demasiados intentos fallidos. Intenta de nuevo en ' .
                $minutos . ($minutos === 1 ? ' minuto.' : ' minutos.'),
        ], 429);
    }

    public function logout(Request $request)
    {
        $usuario = Auth::user();

        if ($usuario) {

            AuditLogService::log(
                module: 'autenticacion',
                action: 'LOGOUT',
                description: 'El usuario "' . $usuario->username .
                '" cerró sesión.',
                entity: $usuario
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function register(
        Request $request,
        SystemSettings $settings,
        PasswordPolicy $politica,
        UsuarioService $usuarios
    ) {
        if (! $settings->get('registration_enabled', true)) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El registro de nuevos usuarios está deshabilitado.',
            ], 403);
        }

        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $datos = $request->validate([
            'username' => $usuarios->reglasUsername(),
            ...$usuarios->reglasPersona(),
            'password' => $politica->rules(),
        ], [
            ...$usuarios->mensajesUsername(),
            ...$usuarios->mensajesPersona(),
            ...$politica->messages(),
        ]);

        $rolUsuario = $usuarios->rolPredeterminado();

        if (!$rolUsuario) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No fue posible completar el registro. El rol predeterminado no está configurado.',
            ], 500);
        }

        // Primero el usuario, luego la persona con su usuario_id (una sola transacción).
        $usuario = $usuarios->crear(
            [
                'username' => $datos['username'],
                'password' => $datos['password'],
                'activo' => true,
            ],
            $datos,
            [$rolUsuario->id]
        );

        AuditLogService::log(
            module: 'autenticacion',
            action: 'REGISTRO',
            description: 'Se registró el usuario "' . $usuario->username .
            '" (' . $usuario->name . ', ' . $usuario->email .
            ') y se le asignó el rol "usuario".',
            entity: $usuario
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Cuenta creada correctamente. Ya puedes iniciar sesión.',
            'username' => $usuario->username,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
        ]);

        $estado = Password::sendResetLink([
            'email' => $datos['email'],
        ]);

        if ($estado === Password::RESET_LINK_SENT) {

            return response()->json([
                'success' => true,
                'mensaje' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
            ]);
        }

        return response()->json([
            'success' => true,
            'mensaje' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
        ]);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.login', array_merge([
            'token' => $token,
            'email' => $request->query('email'),
            'authPanel' => 'reset',
        ], $this->datosAuth()));
    }

    public function resetPassword(Request $request, PasswordPolicy $politica)
    {
        $datos = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => $politica->rules(),
        ], [
            'token.required' => 'El enlace para restablecer la contraseña no es válido.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',

            ...$politica->messages(),
        ]);

        $estado = Password::reset(
            $datos,
            function (User $usuario, string $password) {

                $usuario->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                AuditLogService::log(
                    module: 'autenticacion',
                    action: 'RECUPERAR_PASSWORD',
                    description: 'Se restableció la contraseña del usuario "' .
                    $usuario->username . '".',
                    entity: $usuario
                );
            }
        );

        if ($estado === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'mensaje' => 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.',
                'redirect' => route('login'),
            ]);
        }

        return response()->json([
            'success' => false,
            'mensaje' => 'El enlace para restablecer la contraseña no es válido o ha expirado.',
        ], 422);
    }
}