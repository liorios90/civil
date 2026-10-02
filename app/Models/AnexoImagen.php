<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnexoImagen extends Model
{
    use ProtegidoPorEmpresa;
    protected $table = 'anexo_imagenes';

    protected $fillable = ['anexo_id', 'ruta', 'orden'];

    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class);
    }

    public function contratoParaAcceso(): ?Contrato
    {
        return $this->anexo?->planillaRubro?->planilla?->contrato;
    }
}
