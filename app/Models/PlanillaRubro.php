<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PlanillaRubro extends Model
{
    use ProtegidoPorEmpresa, RegistraCambios;

    public function contextoHistorial(): array
    {
        $rubro = $this->rubro;

        return [
            'contrato_id' => $this->planilla?->contrato_id,
            'frente_id' => $rubro?->frente_id,
            'planilla_rubro_id' => $this->id,
            'descripcion' => 'Cantidades del rubro '.($rubro?->numero ?? '').': '.Str::limit((string) $rubro?->descripcion, 400),
        ];
    }

    protected function anotarEnHistorial(string $accion): bool
    {
        if ($accion !== 'creado') {
            return true;
        }

        return (float) $this->cantidad_anterior != 0.0 || (float) $this->cantidad_actual != 0.0;
    }
    protected $fillable = [
        'planilla_id', 'rubro_id', 'cantidad_anterior', 'cantidad_actual',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_anterior' => 'decimal:4',
            'cantidad_actual' => 'decimal:4',
        ];
    }

    public function planilla(): BelongsTo
    {
        return $this->belongsTo(Planilla::class);
    }

    public function rubro(): BelongsTo
    {
        return $this->belongsTo(Rubro::class);
    }

    public function contratoParaAcceso(): ?Contrato
    {
        return $this->planilla?->contrato;
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(Anexo::class);
    }
}
