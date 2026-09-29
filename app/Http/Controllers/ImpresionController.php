<?php

namespace App\Http\Controllers;

use App\Models\Planilla;
use App\Models\PlanillaRubro;
use App\Services\PlanillaCalculator;

class ImpresionController extends Controller
{
    public function planilla(Planilla $planilla, PlanillaCalculator $calculator)
    {
        $planilla->load('contrato');
        $liquidacion = $calculator->liquidar($planilla);
        $liquidacion['frentes'] = collect($liquidacion['frentes'])
            ->sortBy(fn ($grupo) => $grupo['frente']->orden)
            ->map(function ($grupo) {
                $grupo['lineas'] = collect($grupo['lineas'])->sortBy(fn ($linea) => $linea['rubro']->numero)->values()->all();

                return $grupo;
            })
            ->values()
            ->all();

        return view('impresion.planilla', [
            'planilla' => $planilla,
            'liquidacion' => $liquidacion,
        ]);
    }

    public function anexo(PlanillaRubro $ejecucion, PlanillaCalculator $calculator)
    {
        $ejecucion->load(['planilla.contrato', 'rubro.frente', 'anexos.lineas', 'anexos.imagenes']);
        $anexo = $ejecucion->anexos()->firstOrCreate(['hoja' => 1], ['tipo' => 'geometrico']);
        $anexo->load(['lineas', 'imagenes']);
        $calculo = $calculator->linea(
            $ejecucion->rubro,
            (float) $ejecucion->cantidad_anterior,
            (float) $ejecucion->cantidad_actual,
        );

        return view('impresion.anexo', [
            'ejecucion' => $ejecucion,
            'anexo' => $anexo,
            'calculo' => $calculo,
        ]);
    }
}
