<?php

namespace App\Models\Concerns;

use App\Models\Contrato;
use App\Models\HistorialCambio;
use App\Models\User;
use App\Services\Historial;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guarda quién creó y quién modificó la fila, y deja cada alta, cambio o baja en historial_cambios.
 */
trait RegistraCambios
{
    /**
     * @var array<int, string>
     */
    private static array $camposSinHistorial = [
        'id', 'created_at', 'updated_at', 'creado_por', 'modificado_por',
        'empresa_id', 'contrato_id', 'frente_id', 'rubro_id', 'planilla_id', 'anexo_id',
        'orden', 'codigo', 'tipo_hoja', 'es_catalogo', 'enlace_fiscalizador',
    ];

    public static function bootRegistraCambios(): void
    {
        static::creating(function ($modelo) {
            if ($id = auth()->id()) {
                $modelo->creado_por ??= $id;
                $modelo->modificado_por = $id;
            }
        });

        static::updating(function ($modelo) {
            if (($id = auth()->id()) && $modelo->huboCambiosParaHistorial()) {
                $modelo->modificado_por = $id;
            }
        });

        static::created(fn ($modelo) => $modelo->anotarCambio('creado'));
        static::updated(fn ($modelo) => $modelo->anotarCambio('modificado'));
        static::deleted(fn ($modelo) => $modelo->anotarCambio('eliminado'));
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function modificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modificado_por');
    }

    /**
     * @return array{empresa_id?: ?int, contrato_id: ?int, frente_id: ?int, planilla_rubro_id: ?int, descripcion: string}
     */
    abstract public function contextoHistorial(): array;

    protected function anotarEnHistorial(string $accion): bool
    {
        return true;
    }

    public function huboCambiosParaHistorial(): bool
    {
        return array_diff(array_keys($this->getDirty()), self::$camposSinHistorial) !== [];
    }

    protected function anotarCambio(string $accion): void
    {
        if (Historial::silenciado() || ! $this->anotarEnHistorial($accion)) {
            return;
        }

        $cambios = [];
        if ($accion === 'modificado') {
            foreach ($this->getChanges() as $campo => $nuevo) {
                if (in_array($campo, self::$camposSinHistorial, true)) {
                    continue;
                }
                $cambios[$campo] = ['antes' => $this->getRawOriginal($campo), 'despues' => $nuevo];
            }
            if ($cambios === []) {
                return;
            }
        } else {
            $clave = $accion === 'creado' ? 'despues' : 'antes';
            foreach ($this->getAttributes() as $campo => $valor) {
                if (in_array($campo, self::$camposSinHistorial, true) || $valor === null || $valor === '') {
                    continue;
                }
                $cambios[$campo] = [$clave => $valor];
            }
        }

        $usuario = auth()->user();
        $contexto = $this->contextoHistorial();
        $empresaId = $contexto['empresa_id']
            ?? ($contexto['contrato_id'] ? Contrato::whereKey($contexto['contrato_id'])->value('empresa_id') : null)
            ?? $usuario?->empresa_id;
        HistorialCambio::create($contexto + [
            'empresa_id' => $empresaId,
            'user_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->name,
            'modelo' => class_basename($this),
            'modelo_id' => $this->getKey(),
            'accion' => $accion,
            'cambios' => $cambios ?: null,
        ]);
    }
}
