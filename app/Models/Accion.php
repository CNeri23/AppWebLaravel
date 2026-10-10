<?php

namespace App\Models;

use App\Support\IconoSeguro;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accion extends Model
{
    use HasFactory;

    protected $table = 'acciones';

    protected $fillable = [
        'submodulo_id',
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

    public function submodulo(): BelongsTo
    {
        return $this->belongsTo(Submodulo::class);
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'accion_id',
            'role_id'
        );
    }
}