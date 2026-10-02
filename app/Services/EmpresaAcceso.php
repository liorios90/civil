<?php

namespace App\Services;

use App\Models\Contrato;

class EmpresaAcceso
{
    public static function autorizar(?Contrato $contrato): void
    {
        $user = auth()->user();
        $mismaEmpresa = $contrato
            && $user
            && $user->empresa_id
            && (int) $contrato->empresa_id === (int) $user->empresa_id;
        $permitido = $mismaEmpresa && (
            $user->esAdministrador()
            || ($user->esUsuario() && $user->contratos()->whereKey($contrato->id)->exists())
        );
        abort_unless($permitido, 404);
    }
}
