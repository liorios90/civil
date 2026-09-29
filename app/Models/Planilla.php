<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planilla extends Model
{
    protected $fillable = [
        'contrato_id', 'numero', 'periodo_desde', 'periodo_hasta',
        'estado', 'iva_porcentaje', 'descuentos', 'multas',
    ];

    protected function casts(): array
    {
        return [
            'periodo_desde' => 'date',
            'periodo_hasta' => 'date',
            'iva_porcentaje' => 'decimal:2',
            'descuentos' => 'decimal:2',
            'multas' => 'decimal:2',
        ];
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function ejecuciones(): HasMany
    {
        return $this->hasMany(PlanillaRubro::class);
    }
}
