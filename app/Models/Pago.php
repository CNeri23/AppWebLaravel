<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'membresia_id',
        'monto',
        'metodo_pago',
        'monto_recibido',
        'cambio',
        'referencia',
        'fecha_pago',
        'observaciones',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class);
    }
}