<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

    public function direccion(): BelongsTo
    {
        return $this->belongsTo(Direccion::class);
    }

    public function tipos(): BelongsToMany
    {
        return $this->belongsToMany(Tipo::class);
    }
}