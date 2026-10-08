<?php

namespace App\Auth;

use App\Models\Persona;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Igual que el proveedor Eloquent, pero cuando se busca por "email"
 * (recuperación de contraseña) el correo se toma de la persona,
 * porque la tabla users ya no tiene correo.
 */
class PersonaUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (isset($credentials['email']) && !isset($credentials['username'])) {
            $persona = Persona::query()
                ->where('email', $credentials['email'])
                ->whereNotNull('usuario_id')
                ->first();

            return $persona?->usuario;
        }

        return parent::retrieveByCredentials($credentials);
    }
}
