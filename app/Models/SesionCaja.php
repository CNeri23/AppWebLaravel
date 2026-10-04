<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesionCaja extends Model
{
    use HasFactory;

    public const ESTADO_ABIERTA = 'abierta';
    public const ESTADO_CERRADA = 'cerrada';

    protected $table = 'sesiones_caja';

    protected $fillable = [
        'caja_id',
        'usuario_apertura_id',
        'usuario_cierre_id',
        'usuario_autorizacion_id',
        'fecha_apertura',
        'fecha_cierre',
        'fondo_inicial',
        'efectivo_esperado',
        'efectivo_contado',
        'diferencia',
        'estado',
        'observaciones_apertura',
        'observaciones_cierre',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'fondo_inicial' => 'decimal:2',
        'efectivo_esperado' => 'decimal:2',
        'efectivo_contado' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuarioApertura(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_apertura_id');
    }

    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_cierre_id');
    }

    public function usuarioAutorizacion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_autorizacion_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'sesion_caja_id');
    }
}