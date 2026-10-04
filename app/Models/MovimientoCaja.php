<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCaja extends Model
{
    use HasFactory;

    public const TIPO_ENTRADA = 'entrada';
    public const TIPO_SALIDA = 'salida';

    public const CONCEPTO_PAGO_MEMBRESIA = 'pago_membresia';
    public const CONCEPTO_RETIRO = 'retiro';
    public const CONCEPTO_GASTO = 'gasto';
    public const CONCEPTO_COMPRA = 'compra';
    public const CONCEPTO_AJUSTE = 'ajuste';

    protected $table = 'movimientos_caja';

    protected $fillable = [
        'sesion_caja_id',
        'pago_id',
        'usuario_id',
        'tipo',
        'concepto',
        'monto',
        'fecha_movimiento',
        'referencia',
        'observaciones',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
    ];

    public function sesionCaja(): BelongsTo
    {
        return $this->belongsTo(SesionCaja::class, 'sesion_caja_id');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}