<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaFinanciera extends Model
{
    protected $table = 'categorias_financieras';

    protected $fillable = [
        'ambito',
        'user_id',
        'nombre',
        'tipo',
        'descripcion',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoFinanciero::class, 'categoria_financiera_id');
    }
}
