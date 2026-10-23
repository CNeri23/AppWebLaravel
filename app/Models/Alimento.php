<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alimento extends Model
{
    protected $fillable = ['nombre','categoria','unidad_base','nombre_porcion','cantidad_porcion','calorias','proteinas','carbohidratos','grasas','activo'];

    protected function casts(): array
    {
        return ['cantidad_porcion'=>'decimal:2','calorias'=>'decimal:2','proteinas'=>'decimal:2','carbohidratos'=>'decimal:2','grasas'=>'decimal:2','activo'=>'boolean'];
    }

    public function detallesComida(): HasMany
    {
        return $this->hasMany(RegistroComidaDetalle::class);
    }
}
