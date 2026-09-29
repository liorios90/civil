<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anexo extends Model
{
    protected $fillable = ['planilla_rubro_id', 'tipo', 'hoja'];

    public function planillaRubro(): BelongsTo
    {
        return $this->belongsTo(PlanillaRubro::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(MedicionLinea::class)->orderBy('orden');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(AnexoImagen::class)->orderBy('orden');
    }
}
