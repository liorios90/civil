<?php

namespace App\Http\Controllers;

use App\Models\Frente;
use App\Models\Rubro;
use App\Services\FrenteRubros;
use App\Services\PlanillaCalculator;
use Illuminate\Http\Request;

class RubroController extends Controller
{
    public function guardar(Request $request, Frente $frente)
    {
        $frente->asegurarEditable();
        $filas = $request->validate([
            'filas' => ['nullable', 'array'],
            'filas.*.id' => ['nullable', 'integer'],
            'filas.*.descripcion' => ['nullable', 'string'],
            'filas.*.unidad' => ['nullable', 'string', 'max:20'],
            'filas.*.cantidad_contratada' => ['nullable', 'numeric'],
            'filas.*.precio_unitario' => ['nullable', 'numeric'],
            'filas.*.cantidad_anterior' => ['nullable', 'numeric'],
            'filas.*.cantidad_actual' => ['nullable', 'numeric'],
            'filas.*.tipo_hoja' => ['nullable', 'in:valores,imagenes'],
        ])['filas'] ?? [];

        foreach ($filas as $fila) {
            $descripcion = trim((string) ($fila['descripcion'] ?? ''));
            $existente = ! empty($fila['id'])
                ? Rubro::where('frente_id', $frente->id)->find($fila['id'])
                : null;

            if (! array_key_exists('descripcion', $fila)) {
                if ($existente && array_key_exists('unidad', $fila) && trim((string) $fila['unidad']) !== '') {
                    $existente->update(['unidad' => $fila['unidad']]);
                }
                if ($existente) {
                    $this->guardarCantidades($frente, $existente);
                }
                continue;
            }

            if ($descripcion === '') {
                continue;
            }

            $datos = [
                'descripcion' => $descripcion,
                'unidad' => ($fila['unidad'] ?? '') !== '' ? $fila['unidad'] : 'u',
                'cantidad_contratada' => round((float) ($fila['cantidad_contratada'] ?? 0), 2),
                'precio_unitario' => round((float) ($fila['precio_unitario'] ?? 0), 2),
                'tipo_hoja' => ($fila['tipo_hoja'] ?? 'valores') === 'imagenes' ? 'imagenes' : 'valores',
            ];

            if ($existente) {
                $existente->update([
                    'descripcion' => $datos['descripcion'],
                    'unidad' => $datos['unidad'],
                    'tipo_hoja' => $datos['tipo_hoja'],
                ]);
                $rubro = $existente;
            } else {
                $catalogo = app(FrenteRubros::class);
                $rubro = $frente->rubros()->create($datos + ['numero' => $catalogo->siguienteNumero($frente)]);
                $catalogo->reflejarNuevo($rubro);
            }

            $this->guardarCantidades($frente, $rubro);
        }

        return redirect()->route('frentes.show', $frente)->with('estado', 'Hoja guardada.');
    }

    public function store(Request $request, Frente $frente)
    {
        $frente->asegurarEditable();
        $data = $this->datos($request);
        $catalogo = app(FrenteRubros::class);
        $rubro = $frente->rubros()->create([
            'numero' => $catalogo->siguienteNumero($frente),
            'descripcion' => $data['descripcion'],
            'unidad' => $data['unidad'],
            'cantidad_contratada' => round((float) $data['cantidad_contratada'], 2),
            'precio_unitario' => round((float) $data['precio_unitario'], 2),
        ]);

        $catalogo->reflejarNuevo($rubro);
        $this->guardarCantidades($frente, $rubro);

        return redirect()->route('frentes.show', $frente);
    }

    public function update(Request $request, Rubro $rubro)
    {
        $rubro->frente->asegurarEditable();
        $data = $this->datos($request);
        $rubro->update([
            'descripcion' => $data['descripcion'],
            'unidad' => $data['unidad'],
            'cantidad_contratada' => round((float) $data['cantidad_contratada'], 2),
            'precio_unitario' => round((float) $data['precio_unitario'], 2),
        ]);
        $this->guardarCantidades($rubro->frente, $rubro);

        return redirect()->route('frentes.show', $rubro->frente);
    }

    public function destroy(Rubro $rubro)
    {
        $frente = $rubro->frente;
        $frente->asegurarEditable();
        $rubro->delete();

        return redirect()->route('frentes.show', $frente);
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Request $request): array
    {
        return $request->validate([
            'descripcion' => ['required', 'string'],
            'unidad' => ['required', 'string', 'max:20'],
            'cantidad_contratada' => ['required', 'numeric', 'min:0'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'cantidad_anterior' => ['nullable', 'numeric'],
            'cantidad_actual' => ['nullable', 'numeric'],
        ]);
    }

    private function guardarCantidades(Frente $frente, Rubro $rubro): void
    {
        $planilla = $frente->contrato->planillas()->latest('id')->first();
        if (! $planilla) {
            return;
        }

        $anterior = app(PlanillaCalculator::class)->anteriorPagado($planilla, $rubro->id);
        $ejecucion = $planilla->ejecuciones()->firstOrCreate(
            ['rubro_id' => $rubro->id],
            ['cantidad_anterior' => $anterior, 'cantidad_actual' => 0],
        );

        $ejecucion->update(['cantidad_anterior' => $anterior]);
    }
}
