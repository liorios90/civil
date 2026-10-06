<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogoRubro extends Model
{
    protected $fillable = [
        'empresa_id', 'numero', 'descripcion', 'unidad',
        'cantidad_contratada', 'precio_unitario',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_contratada' => 'decimal:4',
            'precio_unitario' => 'decimal:4',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
