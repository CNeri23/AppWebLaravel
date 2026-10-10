<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'icono',
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

    public function submodulos(): HasMany
    {
        return $this->hasMany(Submodulo::class);
    }
}