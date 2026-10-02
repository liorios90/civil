<?php

namespace App\Models\Concerns;

use App\Models\Contrato;
use App\Services\EmpresaAcceso;

trait ProtegidoPorEmpresa
{
    public function resolveRouteBinding($value, $field = null)
    {
        $modelo = static::query()->where($field ?? $this->getRouteKeyName(), $value)->firstOrFail();
        EmpresaAcceso::autorizar($modelo->contratoParaAcceso());

        return $modelo;
    }

    abstract public function contratoParaAcceso(): ?Contrato;
}
