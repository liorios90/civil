<?php

namespace App\Models;

use App\Models\Concerns\ProtegidoPorEmpresa;
use App\Models\Concerns\RegistraCambios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AnexoImagen extends Model
{
    use ProtegidoPorEmpresa, RegistraCambios;

    public function contextoHistorial(): array
    {
        $ejecucion = $this->anexo?->planillaRubro;
        $rubro = $ejecucion?->rubro;
        $seccion = str_starts_with((string) $this->ruta, 'anexos/otras/') ? 'Otras imágenes' : 'Imágenes';

        return [
            'contrato_id' => $ejecucion?->planilla?->contrato_id,
            'frente_id' => $rubro?->frente_id,
            'planilla_rubro_id' => $ejecucion?->id,
            'descripcion' => $seccion.' del rubro '.($rubro?->numero ?? '').': '.Str::limit((string) $rubro?->descripcion, 300),
        ];
    }
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
