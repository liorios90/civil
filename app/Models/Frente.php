<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Frente extends Model
{
    use ProtegidoPorEmpresa;
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
}
