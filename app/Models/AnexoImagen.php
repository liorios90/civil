<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnexoImagen extends Model
{
    protected $table = 'anexo_imagenes';

    protected $fillable = ['anexo_id', 'ruta', 'orden'];

    public function anexo(): BelongsTo
    {
        return $this->belongsTo(Anexo::class);
    }
}
