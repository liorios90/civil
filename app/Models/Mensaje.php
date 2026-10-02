<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensaje extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'empresa_id',
        'de_id',
        'para_id',
        'texto',
        'leido_at',
    ];

    protected function casts(): array
    {
        return [
            'leido_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function de(): BelongsTo
    {
        return $this->belongsTo(User::class, 'de_id');
    }

    public function para(): BelongsTo
    {
        return $this->belongsTo(User::class, 'para_id');
    }
}
