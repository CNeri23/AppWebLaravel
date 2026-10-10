<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submodulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'modulo_id',
        'nombre',
        'slug',
        'descripcion',
        'icono',
        'ruta',
        'orden',
        'activo',
    ];

    public function getIconoAttribute(?string $value): ?string
    {
        return IconoSeguro::sanitizar($value);
    }

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    public function acciones(): HasMany
    {
        return $this->hasMany(Accion::class);
    }
}