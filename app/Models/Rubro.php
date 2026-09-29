<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubro extends Model
{
    protected $fillable = [
        'frente_id', 'numero', 'codigo', 'descripcion', 'unidad',
        'cantidad_contratada', 'precio_unitario', 'tipo_hoja',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_contratada' => 'decimal:4',
            'precio_unitario' => 'decimal:4',
        ];
    }

    public function frente(): BelongsTo
    {
        return $this->belongsTo(Frente::class);
    }

    public function planillaRubros(): HasMany
    {
        return $this->hasMany(PlanillaRubro::class);
    }
}
