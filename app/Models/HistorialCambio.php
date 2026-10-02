<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialCambio extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'historial_cambios';

    protected $fillable = [
        'empresa_id', 'contrato_id', 'frente_id', 'planilla_rubro_id', 'user_id', 'usuario_nombre',
        'modelo', 'modelo_id', 'accion', 'descripcion', 'cambios',
    ];

    protected function casts(): array
    {
        return [
            'cambios' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function nombreUsuario(): string
    {
        return $this->usuario?->name ?? $this->usuario_nombre ?? 'Sistema';
    }
}
