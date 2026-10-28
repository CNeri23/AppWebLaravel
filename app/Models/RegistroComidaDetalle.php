<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroComidaDetalle extends Model
{
    protected $table = 'registro_comida_detalles';
    protected $fillable = ['registro_comida_id','alimento_id','nombre_alimento','cantidad','unidad','calorias','proteinas','carbohidratos','grasas'];

    protected function casts(): array
    {
        return ['cantidad'=>'decimal:2','calorias'=>'decimal:2','proteinas'=>'decimal:2','carbohidratos'=>'decimal:2','grasas'=>'decimal:2'];
    }

    public function registro(): BelongsTo
    {
        return $this->belongsTo(RegistroComida::class, 'registro_comida_id');
    }

    public function alimento(): BelongsTo
    {
        return $this->belongsTo(Alimento::class);
    }
}
