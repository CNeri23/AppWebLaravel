<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'theme_mode',
        'light_theme_style',
        'dark_theme_style',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
