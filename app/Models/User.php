<?php

namespace App\Models;

use App\Models\UserPreference;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * El usuario solo sirve para entrar al sistema (username, contraseña y estado).
 * Nombre, correo y teléfono viven en la persona ligada (personas.usuario_id).
 *
 * @property-read string $name   Nombre completo de la persona (o el username si no tiene persona)
 * @property-read ?string $email Correo de la persona
 */
class User extends Authenticatable implements CanResetPasswordContract
{
    use HasFactory, Notifiable, CanResetPassword;

    protected $fillable = [
        'username',
        'password',
        'activo',
        'profile_image',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** La persona casi siempre se necesita (nombre en menú, bitácora, tickets…). */
    protected $with = ['persona'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function persona(): HasOne
    {
        return $this->hasOne(Persona::class, 'usuario_id');
    }

    public function preference(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions()
    {
        return RolePermission::query()
            ->whereIn('role_id', $this->roles()->pluck('roles.id'))
            ->get();
    }

    /**
     * Nombre para mostrar: lo que antes era users.name.
     */
    public function getNameAttribute(): string
    {
        return $this->persona?->nombre_completo ?: (string) $this->username;
    }

    /**
     * Correo: lo que antes era users.email (se usa en recuperación de contraseña).
     */
    public function getEmailAttribute(): ?string
    {
        return $this->persona?->email;
    }
}
