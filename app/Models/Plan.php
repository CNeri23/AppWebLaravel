<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'duracion_dias',
        'precio',
        'activo',
    ];

    protected $casts = [
        'duracion_dias' => 'integer',
        'precio' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
