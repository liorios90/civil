<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MedicionLinea extends Model
{
    use ProtegidoPorEmpresa, RegistraCambios;

    public function contextoHistorial(): array
    {
        $ejecucion = $this->anexo?->planillaRubro;
        $rubro = $ejecucion?->rubro;
        $texto = $this->descripcion ? ' ('.Str::limit((string) $this->descripcion, 150).')' : '';

        return [
            'contrato_id' => $ejecucion?->planilla?->contrato_id,
            'frente_id' => $rubro?->frente_id,
            'planilla_rubro_id' => $ejecucion?->id,
            'descripcion' => 'Medición '.$this->orden.$texto.' del rubro '.($rubro?->numero ?? '').': '.Str::limit((string) $rubro?->descripcion, 250),
        ];
    }
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

    public function contratoParaAcceso(): ?Contrato
    {
        return $this->anexo?->planillaRubro?->planilla?->contrato;
    }
}
