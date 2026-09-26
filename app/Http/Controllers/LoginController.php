<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {

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

            return redirect()->intended('/dashboard');
        }

        AuditLogService::log(
            module: 'autenticacion',
            action: 'LOGIN_FALLIDO',
            description: 'Se intentó iniciar sesión con el correo "' .
                $request->email . '", pero las credenciales no fueron correctas.'
        );

        return back()->withErrors([
            'email' => 'Las credenciales no son correctas.',
        ])->onlyInput('email');
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
}