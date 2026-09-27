<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', [
            'token' => null,
            'email' => null,
            'authPanel' => 'login',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            $request->session()->regenerate();

            $usuario = Auth::user();

            AuditLogService::log(
                module: 'autenticacion',
                action: 'LOGIN',
                description: 'El usuario "' . $usuario->name .
                '" inició sesión con el correo "' .
                $usuario->email . '".',
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
            description: 'Se intentó iniciar sesión con el correo "' .
            $request->email . '", pero las credenciales no fueron correctas.'
        );

        return response()->json([
            'success' => false,
            'mensaje' => 'Las credenciales no son correctas.',
        ], 401);
    }

    public function logout(Request $request)
    {
        $usuario = Auth::user();

        if ($usuario) {

            AuditLogService::log(
                module: 'autenticacion',
                action: 'LOGOUT',
                description: 'El usuario "' . $usuario->name .
                '" cerró sesión.',
                entity: $usuario
            );
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function register(Request $request)
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre no es válido.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.string' => 'El correo electrónico no es válido.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
        ]);

        $rolUsuario = Role::where('name', 'usuario')->first();

        if (!$rolUsuario) {

            $usuario->delete();

            return response()->json([
                'success' => false,
                'mensaje' => 'No fue posible completar el registro. El rol predeterminado no está configurado.',
            ], 500);
        }

        $usuario->roles()->attach($rolUsuario->id);

        AuditLogService::log(
            module: 'autenticacion',
            action: 'REGISTRO',
            description: 'Se registró el usuario "' .
            $usuario->name . '" con el correo "' .
            $usuario->email . '" y se le asignó el rol "usuario".',
            entity: $usuario
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Cuenta creada correctamente. Ya puedes iniciar sesión.',
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
        return view('auth.login', [
            'token' => $token,
            'email' => $request->query('email'),
            'authPanel' => 'reset',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $datos = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'token.required' => 'El enlace para restablecer la contraseña no es válido.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
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
                    $usuario->name . '".',
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