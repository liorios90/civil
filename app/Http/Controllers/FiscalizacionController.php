<?php

namespace App\Http\Controllers;

use App\Models\AnexoImagen;
use App\Models\Contrato;
use App\Models\PlanillaRubro;
use App\Services\PlanillaCalculator;
use Illuminate\Support\Facades\Storage;

class FiscalizacionController extends Controller
{
    public function show(string $token, PlanillaCalculator $calculator)
    {
        $contrato = $this->contrato($token);
        $planilla = $contrato->planillas()->latest('id')->first();

        return view('fiscalizacion.planilla', [
            'contrato' => $contrato,
            'planilla' => $planilla,
            'liquidacion' => $planilla ? $this->liquidacion($planilla, $calculator) : ['frentes' => [], 'totales' => null],
            'token' => $token,
        ]);
    }

    public function hoja(string $token, int $ejecucion, PlanillaCalculator $calculator)
    {
        $contrato = $this->contrato($token);
        $ejecucion = $this->ejecucion($contrato, $ejecucion);
        $ejecucion->load(['planilla', 'rubro.frente', 'anexos.lineas', 'anexos.imagenes']);
        $anexo = $ejecucion->anexos->first();
        $calculo = $calculator->linea(
            $ejecucion->rubro,
            (float) $ejecucion->cantidad_anterior,
            (float) $ejecucion->cantidad_actual,
        );

        return view('fiscalizacion.hoja', [
            'contrato' => $contrato,
            'ejecucion' => $ejecucion,
            'anexo' => $anexo,
            'calculo' => $calculo,
            'token' => $token,
        ]);
    }

    public function archivo(string $token, string $ruta)
    {
        $contrato = $this->contrato($token);
        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');
        abort_if($ruta === '' || str_contains($ruta, '..'), 404);

        $imagen = AnexoImagen::query()->where('ruta', $ruta)->first();
        abort_unless($imagen && $this->imagenDelContrato($imagen, $contrato), 404);
        abort_unless(Storage::disk('public')->exists($ruta), 404);

        return Storage::disk('public')->response($ruta);
    }

    private function contrato(string $token): Contrato
    {
        $contrato = Contrato::query()
            ->with('empresa')
            ->where('enlace_fiscalizador', $token)
            ->firstOrFail();
        abort_unless($contrato->empresa?->activo, 404);

        return $contrato;
    }

    private function ejecucion(Contrato $contrato, int $id): PlanillaRubro
    {
        $ejecucion = PlanillaRubro::query()
            ->whereKey($id)
            ->whereHas('planilla', fn ($q) => $q->where('contrato_id', $contrato->id))
            ->whereHas('rubro.frente', fn ($q) => $q->where('es_catalogo', false))
            ->firstOrFail();

        return $ejecucion;
    }

    private function imagenDelContrato(AnexoImagen $imagen, Contrato $contrato): bool
    {
        $contratoId = $imagen->anexo?->planillaRubro?->planilla?->contrato_id;

        return (int) $contratoId === (int) $contrato->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function liquidacion($planilla, PlanillaCalculator $calculator): array
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
}
