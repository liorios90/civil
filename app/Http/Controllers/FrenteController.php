<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Frente;
use App\Models\PlanillaRubro;
use App\Services\FrenteRubros;
use App\Services\Historial;
use App\Services\PlanillaCalculator;
use Illuminate\Http\Request;

class FrenteController extends Controller
{
    public function store(Request $request, Contrato $contrato, FrenteRubros $catalogo)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:500'],
        ]);

        $orden = (int) $contrato->frentes()->max('orden') + 1;
        $frente = $contrato->frentes()->create([
            'nombre' => $data['nombre'],
            'numero' => $orden,
            'orden' => $orden,
        ]);
        Historial::sinRegistro(fn () => $catalogo->clonarDesdeAnterior($frente));

        return redirect()->route('frentes.show', $frente)->with('estado', 'Frente creado. Aquí van sus rubros y cantidades.');
    }

    public function show(Frente $frente, PlanillaCalculator $calculator)
    {
        $frente->load('contrato.planillas', 'rubros');
        $planilla = $frente->contrato->planillas->sortByDesc('id')->first();
        $ejecuciones = collect();
        if ($planilla && ($planilla->estado ?: 'borrador') === 'borrador') {
            foreach ($frente->rubros as $rubro) {
                $planilla->ejecuciones()->firstOrCreate(
                    ['rubro_id' => $rubro->id],
                    ['cantidad_anterior' => $calculator->anteriorPagado($planilla, $rubro->id), 'cantidad_actual' => 0],
                );
            }
            $calculator->fijarAnteriores($planilla);
        }
        if ($planilla) {
            $ejecuciones = PlanillaRubro::with('anexos.lineas')
                ->where('planilla_id', $planilla->id)
                ->whereIn('rubro_id', $frente->rubros->pluck('id'))
                ->get()
                ->keyBy('rubro_id');
        }

        $lineas = [];
        $totales = ['contratado' => 0.0, 'anterior' => 0.0, 'actual' => 0.0, 'acumulado' => 0.0];
        foreach ($frente->rubros as $rubro) {
            $ejecucion = $ejecuciones->get($rubro->id);
            $calculo = $calculator->linea(
                $rubro,
                (float) ($ejecucion->cantidad_anterior ?? 0),
                (float) ($ejecucion->cantidad_actual ?? 0),
            );
            $lineas[] = [
                'rubro' => $rubro,
                'ejecucion' => $ejecucion,
                'calculo' => $calculo,
            ];
            $totales['contratado'] += $calculo['total_contratado'];
            $totales['anterior'] += $calculo['valor_anterior'];
            $totales['actual'] += $calculo['valor_actual'];
            $totales['acumulado'] += $calculo['valor_total'];
        }
        foreach ($totales as $clave => $valor) {
            $totales[$clave] = round($valor, 2);
        }

        return view('frentes.show', [
            'frente' => $frente,
            'planilla' => $planilla,
            'ejecuciones' => $ejecuciones,
            'lineas' => $lineas,
            'totales' => $totales,
        ]);
    }

    public function update(Request $request, Frente $frente)
    {
        $data = $request->validate(['nombre' => ['required', 'string', 'max:500']]);
        $frente->update($data);

        return redirect()->route('frentes.show', $frente);
    }

    public function destroy(Frente $frente)
    {
        $contrato = $frente->contrato;
        $ultima = $contrato->frentes()->orderByDesc('orden')->first();
        if (! $ultima || $ultima->id !== $frente->id) {
            return redirect()
                ->route('frentes.show', $frente)
                ->with('estado', 'Solo se puede eliminar la última planilla.');
        }

        $frente->delete();

        return redirect()->route('contratos.show', $contrato);
    }
}
