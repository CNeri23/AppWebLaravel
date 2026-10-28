<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoFinanciero extends Model
{
    protected $table = 'movimientos_financieros';

    protected $fillable = [
        'ambito',
        'user_id',
        'categoria_financiera_id',
        'concepto',
        'importe',
        'fecha',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'importe' => 'decimal:2',
            'fecha' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaFinanciera::class, 'categoria_financiera_id');
    }
}
