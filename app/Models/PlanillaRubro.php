<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanillaRubro extends Model
{
    use ProtegidoPorEmpresa;
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
