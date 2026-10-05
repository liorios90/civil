<?php

namespace App\Http\Controllers;

use App\Models\Planilla;
use App\Models\PlanillaRubro;
use App\Services\PlanillaCalculator;
use App\Services\PlanillaExcel;

class ImpresionController extends Controller
{
    public function planilla(Planilla $planilla, PlanillaCalculator $calculator)
    {
        $planilla->load('contrato');

        return view('impresion.planilla', [
            'planilla' => $planilla,
            'liquidacion' => $this->liquidacion($planilla, $calculator),
        ]);
    }

    public function comparacion(Planilla $planilla, PlanillaCalculator $calculator)
    {
        $planilla->load('contrato');
        $liquidacion = $this->liquidacion($planilla, $calculator);
        $planificado = (float) $liquidacion['totales']['contratado'];
        $real = (float) $liquidacion['totales']['acumulado'];

        return view('avance.comparacion', [
            'planilla' => $planilla,
            'liquidacion' => $liquidacion,
            'planificado' => $planificado,
            'real' => $real,
            'brecha' => round($planificado - $real, 2),
            'porcentaje' => $planificado > 0 ? round($real / $planificado * 100, 2) : null,
        ]);
    }

    public function excel(Planilla $planilla, PlanillaCalculator $calculator, PlanillaExcel $excel)
    {
        $planilla->load('contrato');

        return $excel->descargar($planilla, $this->liquidacion($planilla, $calculator));
    }

    /**
     * @return array<string, mixed>
     */
    private function liquidacion(Planilla $planilla, PlanillaCalculator $calculator): array
    {
        $calculator->fijarAnteriores($planilla);
        $liquidacion = $calculator->liquidar($planilla);
        $liquidacion['frentes'] = collect($liquidacion['frentes'])
            ->sortBy(fn ($grupo) => $grupo['frente']->orden)
            ->map(function ($grupo) {
                $grupo['lineas'] = collect($grupo['lineas'])->sortBy(fn ($linea) => $linea['rubro']->numero)->values()->all();

                return $grupo;
            })
            ->values()
            ->all();

        return $liquidacion;
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
