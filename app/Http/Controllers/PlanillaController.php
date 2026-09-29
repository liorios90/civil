<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Planilla;
use App\Services\PlanillaCalculator;

class PlanillaController extends Controller
{
    public function inicio()
    {
        $contrato = Contrato::withCount(['frentes', 'planillas'])->latest()->first();

        return view('inicio', [
            'contrato' => $contrato,
            'planilla' => $contrato?->planillas()->latest()->first(),
        ]);
    }

    public function show(Planilla $planilla, PlanillaCalculator $calculator)
    {
        $planilla->load('contrato');
        $liquidacion = $calculator->liquidar($planilla);

        return view('planillas.show', [
            'planilla' => $planilla,
            'liquidacion' => $liquidacion,
        ]);
    }

    public function contrato(Contrato $contrato)
    {
        $contrato->load(['frentes.rubros', 'planillas']);

        return view('contratos.show', ['contrato' => $contrato]);
    }
}
