<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Persona extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'email',
        'direccion_id',
        'usuario_id',
    ];

    public function getNombreCompletoAttribute(): string
    {
        return trim(
            $this->nombre . ' ' .
            $this->apellido_paterno . ' ' .
            ($this->apellido_materno ?? '')
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class);
    }

    public function tipos(): BelongsToMany
    {
        return $this->belongsToMany(Tipo::class);
    }

    public function membresias(): HasMany
    {
        return $this->hasMany(Membresia::class);
    }
}