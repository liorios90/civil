<?php

namespace App\Http\Controllers;

class InicioController extends Controller
{
    public function index(ContratoController $contratos)
    {
        $user = auth()->user();
        if ($user->esSistemas()) {
            return redirect()->route('empresas.index');
        }
        return $contratos->index();
    }
}
