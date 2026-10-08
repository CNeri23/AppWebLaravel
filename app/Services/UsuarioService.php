<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UsuarioService
{
    /** Campos de persona que acepta el formulario de usuario. */
    public const CAMPOS_PERSONA = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'email',
    ];

    /**
     * Reglas del username (se guarda en minúsculas).
     */
    public function reglasUsername(?int $ignorarUsuarioId = null): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:50',
            'regex:/^[a-z0-9._-]+$/',
            Rule::unique('users', 'username')->ignore($ignorarUsuarioId),
        ];
    }

    public function mensajesUsername(): array
    {
        return [
            'username.required' => 'El usuario es obligatorio.',
            'username.min' => 'El usuario debe tener al menos 3 caracteres.',
            'username.max' => 'El usuario no puede superar los 50 caracteres.',
            'username.regex' => 'El usuario solo puede tener letras, números, punto, guion y guion bajo (sin espacios).',
            'username.unique' => 'Este usuario ya está en uso.',
        ];
    }

    /**
     * Reglas de los datos de persona. El correo es obligatorio porque
     * es el que se usa para recuperar la contraseña.
     */
    public function reglasPersona(?int $ignorarPersonaId = null, bool $emailObligatorio = true): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => [
                $emailObligatorio ? 'required' : 'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('personas', 'email')->ignore($ignorarPersonaId),
            ],
        ];
    }

    public function mensajesPersona(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_paterno.max' => 'El apellido paterno no puede superar los 100 caracteres.',
            'apellido_materno.max' => 'El apellido materno no puede superar los 100 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.max' => 'El correo electrónico no puede superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
        ];
    }

    /**
     * Primero se inserta el usuario y después la persona con su usuario_id,
     * todo en una transacción.
     *
     * @param  array{username: string, password: string, activo?: bool}  $datosUsuario
     * @param  array<string, mixed>  $datosPersona
     * @param  array<int, int>  $roles
     */
    public function crear(array $datosUsuario, array $datosPersona, array $roles = []): User
    {
        return DB::transaction(function () use ($datosUsuario, $datosPersona, $roles) {
            $usuario = User::create([
                'username' => $datosUsuario['username'],
                'password' => $datosUsuario['password'],
                'activo' => $datosUsuario['activo'] ?? true,
            ]);

            Persona::create([
                ...array_intersect_key($datosPersona, array_flip(self::CAMPOS_PERSONA)),
                'usuario_id' => $usuario->id,
            ]);

            if ($roles !== []) {
                $usuario->roles()->sync($roles);
            }

            return $usuario->load(['persona', 'roles']);
        });
    }

    /**
     * Crea el usuario de una persona que todavía no tiene (botón en Miembros).
     * Si la persona no tiene usuario, se inserta el usuario y se le asigna.
     */
    public function crearParaPersona(Persona $persona, array $datosUsuario, array $roles = []): User
    {
        return DB::transaction(function () use ($persona, $datosUsuario, $roles) {
            $usuario = User::create([
                'username' => $datosUsuario['username'],
                'password' => $datosUsuario['password'],
                'activo' => $datosUsuario['activo'] ?? true,
            ]);

            $persona->usuario_id = $usuario->id;
            $persona->save();

            if ($roles !== []) {
                $usuario->roles()->sync($roles);
            }

            return $usuario->load(['persona', 'roles']);
        });
    }

    public function rolPredeterminado(): ?Role
    {
        return Role::where('name', 'usuario')->first();
    }

    /**
     * Datos listos para JSON (tabla de usuarios, perfil, etc.).
     */
    public function presentar(User $usuario): array
    {
        $usuario->loadMissing(['persona', 'roles']);

        $persona = $usuario->persona;

        return [
            'id' => $usuario->id,
            'username' => $usuario->username,
            'activo' => (bool) $usuario->activo,
            'name' => $usuario->name,
            'persona_id' => $persona?->id,
            'nombre' => $persona?->nombre,
            'apellido_paterno' => $persona?->apellido_paterno,
            'apellido_materno' => $persona?->apellido_materno,
            'telefono' => $persona?->telefono,
            'email' => $persona?->email,
            'roles' => $usuario->roles->map(fn ($rol) => ['id' => $rol->id, 'name' => $rol->name])->values(),
            'created_at' => $usuario->created_at?->toIso8601String(),
        ];
    }
}
