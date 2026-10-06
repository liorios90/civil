<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Rubro extends Model
{
    use ProtegidoPorEmpresa, RegistraCambios;

    public function contextoHistorial(): array
    {
        return [
            'contrato_id' => $this->frente?->contrato_id,
            'frente_id' => $this->frente_id,
            'planilla_rubro_id' => null,
            'descripcion' => 'Rubro '.$this->numero.': '.Str::limit((string) $this->descripcion, 400),
        ];
    }
    protected $fillable = [
        'frente_id', 'numero', 'codigo', 'descripcion', 'unidad',
        'cantidad_contratada', 'precio_unitario', 'medicion', 'tipo_hoja',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_contratada' => 'decimal:4',
            'precio_unitario' => 'decimal:4',
            'medicion' => 'array',
        ];
    }

    public function frente(): BelongsTo
    {
        return $this->belongsTo(Frente::class);
    }

    public function contratoParaAcceso(): ?Contrato
    {
        return $this->frente?->contrato;
    }

    public function planillaRubros(): HasMany
    {
        return $this->hasMany(PlanillaRubro::class);
    }
}
