<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicionLinea extends Model
{
    protected $fillable = [
        'anexo_id', 'orden', 'descripcion', 'base1', 'base2', 'altura', 'numero',
        'longitud', 'area', 'volumen', 'total',
    ];

    protected function casts(): array
    {
        return [
            'base1' => 'decimal:4',
            'base2' => 'decimal:4',
            'altura' => 'decimal:4',
            'numero' => 'decimal:4',
            'longitud' => 'decimal:4',
            'area' => 'decimal:4',
            'volumen' => 'decimal:4',
            'total' => 'decimal:4',
        ];
    }

    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class);
    }
}
