<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Planilla extends Model
{
    use ProtegidoPorEmpresa, RegistraCambios;

    public function contextoHistorial(): array
    {
        return [
            'contrato_id' => $this->contrato_id,
            'frente_id' => null,
            'planilla_rubro_id' => null,
            'descripcion' => 'Período de planilla '.$this->numero,
        ];
    }
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

    public function contratoParaAcceso(): ?Contrato
    {
        return $this->contrato;
    }

    public function ejecuciones(): HasMany
    {
        return $this->hasMany(PlanillaRubro::class);
    }
}
