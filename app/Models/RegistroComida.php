<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegistroComida extends Model
{
    protected $table = 'registros_comida';
    protected $fillable = ['usuario_id','persona_id','fecha','tipo_comida','notas'];

    protected function casts(): array
    {
        return ['fecha'=>'date'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RegistroComidaDetalle::class, 'registro_comida_id');
    }
}
