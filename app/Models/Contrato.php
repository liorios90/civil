<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contrato extends Model
{
    protected $fillable = [
        'entidad', 'numero_contrato', 'codigo_proceso', 'objeto',
        'fecha_suscripcion', 'fecha_inicio', 'fecha_termino',
        'ubicacion', 'provincia', 'contratista', 'fiscalizador',
        'administrador', 'plazo', 'monto_contrato', 'monto_contrato_iva',
        'porcentaje_anticipo', 'anticipo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_suscripcion' => 'date',
            'fecha_inicio' => 'date',
            'fecha_termino' => 'date',
            'monto_contrato' => 'decimal:2',
            'monto_contrato_iva' => 'decimal:2',
            'porcentaje_anticipo' => 'decimal:4',
            'anticipo' => 'decimal:2',
        ];
    }

    public function frentes(): HasMany
    {
        return $this->hasMany(Frente::class)->where('es_catalogo', false)->orderBy('orden');
    }

    public function catalogo(): HasOne
    {
        return $this->hasOne(Frente::class)->where('es_catalogo', true);
    }

    public function planillas(): HasMany
    {
        return $this->hasMany(Planilla::class);
    }
}
