<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Frente extends Model
{
    use ProtegidoPorEmpresa, RegistraCambios;

    public function contextoHistorial(): array
    {
        return [
            'contrato_id' => $this->contrato_id,
            'frente_id' => $this->id,
            'planilla_rubro_id' => null,
            'descripcion' => ($this->es_catalogo ? 'Catálogo de rubros' : 'Planilla: '.Str::limit((string) $this->nombre, 400)),
        ];
    }
    protected $fillable = ['contrato_id', 'numero', 'nombre', 'orden', 'es_catalogo'];

    protected function casts(): array
    {
        return [
            'es_catalogo' => 'boolean',
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

    public function rubros(): HasMany
    {
        return $this->hasMany(Rubro::class)->orderBy('numero');
    }

    public function esUltimaPlanilla(): bool
    {
        if ($this->es_catalogo) {
            return false;
        }

        $ultimaId = $this->contrato
            ? $this->contrato->frentes()->reorder()->orderByDesc('orden')->orderByDesc('id')->value('id')
            : null;

        return $ultimaId !== null && (int) $ultimaId === (int) $this->id;
    }

    public function asegurarEditable(): void
    {
        abort_unless($this->esUltimaPlanilla(), 403, 'Esta planilla ya no se puede modificar. Solo se puede visualizar.');
    }
}
