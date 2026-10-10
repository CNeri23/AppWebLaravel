<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\PasswordPolicy;
use App\Services\SystemSettings;
use App\Services\UsuarioService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index(SystemSettings $settings, PasswordPolicy $politica)
    {
        $usuario = Auth::user()->load(['roles', 'persona.direccion', 'persona.tipos']);

        $puedeVerLogs = $usuario->permissions()
            ->contains(function ($permiso) {
                return $permiso->slug === 'logs';
            });

        $ajustes = $settings->all();

        // Formato de fecha/hora y zona horaria definidos en Configuración
        $formatoFecha = $ajustes['date_format'];
        $formatoHora = $ajustes['time_format'];
        $zonaHoraria = $ajustes['timezone'];

        $politicaPassword = [
            'min' => $politica->minLength(),
            'complex' => $politica->requiresComplexity(),
            'descripcion' => $politica->description(),
        ];

        return view('perfil.index', compact(
            'usuario',
            'puedeVerLogs',
            'formatoFecha',
            'formatoHora',
            'zonaHoraria',
            'politicaPassword'
        ));
    }

    public function update(Request $request, UsuarioService $servicio)
    {
        $usuario = Auth::user()->loadMissing('persona');

        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $datos = $request->validate([
            'username' => $servicio->reglasUsername($usuario->id),
            ...$servicio->reglasPersona($usuario->persona?->id),
        ], [
            ...$servicio->mensajesUsername(),
            ...$servicio->mensajesPersona(),
        ]);

        $cambios = [];

        if ($usuario->username !== $datos['username']) {
            $cambios[] = 'usuario';
        }

        $persona = $usuario->persona ?? new \App\Models\Persona(['usuario_id' => $usuario->id]);

        foreach (UsuarioService::CAMPOS_PERSONA as $campo) {
            $nuevo = $datos[$campo] ?? null;

            if ($persona->exists && ($persona->{$campo} ?? null) !== $nuevo) {
                $cambios[] = str_replace('_', ' ', $campo);
            }

            $persona->{$campo} = $nuevo;
        }

        DB::transaction(function () use ($usuario, $persona, $datos) {
            $usuario->username = $datos['username'];
            $usuario->save();

            $persona->usuario_id = $usuario->id;
            $persona->save();
        });

        AuditLogService::log(
            module: 'perfil',
            action: 'ACTUALIZAR_PERFIL',
            description: 'El usuario actualizó su perfil' .
                (!empty($cambios)
                    ? ': ' . implode(', ', $cambios) . '.'
                    : '.'),
            entity: $usuario
        );

        $usuario->refresh()->load('persona');

        return response()->json([
            'success' => true,
            'mensaje' => 'Perfil actualizado correctamente.',
            'usuario' => [
                ...$servicio->presentar($usuario),
                'updated_at' => $usuario->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function updatePassword(Request $request, PasswordPolicy $politica)
    {
        $usuario = Auth::user();

        $datos = $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],
            'password' => $politica->rules(),
        ], [
            ...$politica->messages(),

            'current_password.required' => 'La contraseña actual es obligatoria.',
            'current_password.current_password' => 'La contraseña actual no es correcta.',

            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos ' .
                $politica->minLength() . ' caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $usuario->update([
            'password' => $datos['password'],
        ]);

        // Revocar las demás sesiones cuando el controlador de sesiones usa la base de datos.
        // La sesión actual se conserva para que el usuario no tenga que iniciar sesión otra vez.
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $usuario->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        AuditLogService::log(
            module: 'perfil',
            action: 'CAMBIAR_PASSWORD',
            description: 'El usuario cambió su contraseña desde su perfil.',
            entity: $usuario
        );

        $usuario->refresh();

        return response()->json([
            'success' => true,
            'mensaje' => 'Contraseña actualizada correctamente.',
            'usuario' => [
                'updated_at' => $usuario->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $usuario = Auth::user();

        $datos = $request->validate([
            'profile_image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ], [
            'profile_image.required' => 'Selecciona una imagen.',
            'profile_image.image' => 'El archivo seleccionado no es una imagen válida.',
            'profile_image.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
            'profile_image.max' => 'La imagen no puede superar los 2 MB.',
        ]);

        if ($usuario->profile_image) {
            Storage::disk('public')->delete($usuario->profile_image);
        }

        $ruta = $datos['profile_image']->store('profiles', 'public');

        $usuario->update([
            'profile_image' => $ruta,
        ]);

        AuditLogService::log(
            module: 'perfil',
            action: 'ACTUALIZAR_FOTO',
            description: 'El usuario actualizó su foto de perfil.',
            entity: $usuario
        );

        $usuario->refresh();

        return response()->json([
            'success' => true,
            'mensaje' => 'Foto de perfil actualizada correctamente.',
            'imagen' => asset('storage/' . $ruta),
            'usuario' => [
                'name' => $usuario->name,
                'updated_at' => $usuario->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function deletePhoto()
    {
        $usuario = Auth::user();

        if ($usuario->profile_image) {
            Storage::disk('public')->delete($usuario->profile_image);

            $usuario->update([
                'profile_image' => null,
            ]);
        }

        AuditLogService::log(
            module: 'perfil',
            action: 'ELIMINAR_FOTO',
            description: 'El usuario eliminó su foto de perfil.',
            entity: $usuario
        );

        $usuario->refresh();

        return response()->json([
            'success' => true,
            'mensaje' => 'Foto de perfil eliminada correctamente.',
            'usuario' => [
                'name' => $usuario->name,
                'updated_at' => $usuario->updated_at?->toIso8601String(),
            ],
        ]);
    }
}