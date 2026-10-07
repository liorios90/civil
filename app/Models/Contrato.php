<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Contrato extends Model
{
    use ProtegidoPorEmpresa, RegistraCambios;

    public function contextoHistorial(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'contrato_id' => $this->exists ? $this->id : null,
            'frente_id' => null,
            'planilla_rubro_id' => null,
            'descripcion' => 'Contrato '.$this->codigo_proceso,
        ];
    }

    protected $fillable = [
        'empresa_id', 'entidad', 'numero_contrato', 'codigo_proceso', 'objeto',
        'fecha_suscripcion', 'fecha_inicio', 'fecha_termino',
        'ubicacion', 'provincia', 'contratista', 'fiscalizador',
        'administrador', 'plazo', 'monto_contrato', 'monto_contrato_iva', 'iva_porcentaje',
        'porcentaje_anticipo', 'anticipo', 'enlace_fiscalizador',
    ];

    protected function casts(): array
    {
        return [
            'fecha_suscripcion' => 'date',
            'fecha_inicio' => 'date',
            'fecha_termino' => 'date',
            'monto_contrato' => 'decimal:2',
            'monto_contrato_iva' => 'decimal:2',
            'iva_porcentaje' => 'decimal:2',
            'porcentaje_anticipo' => 'decimal:4',
            'anticipo' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function contratoParaAcceso(): ?Contrato
    {
        return $this;
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
