<?php

namespace App\Http\Controllers;

use App\Models\Frente;
use App\Models\Rubro;
use Illuminate\Http\Request;

class RubroController extends Controller
{
    public function guardar(Request $request, Frente $frente)
    {
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
                if ($existente) {
                    $this->guardarContratado($existente, $fila);
                    $this->guardarCantidades($frente, $existente, $fila);
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
                $existente->update($datos);
                $rubro = $existente;
            } else {
                $numero = (int) $frente->rubros()->max('numero') + 1;
                $rubro = $frente->rubros()->create($datos + ['numero' => $numero]);
            }

            $this->guardarCantidades($frente, $rubro, $fila);
        }

        return redirect()->route('frentes.show', $frente)->with('estado', 'Hoja guardada.');
    }

    public function store(Request $request, Frente $frente)
    {
        $data = $this->datos($request);
        $numero = (int) $frente->rubros()->max('numero') + 1;
        $rubro = $frente->rubros()->create([
            'numero' => $numero,
            'descripcion' => $data['descripcion'],
            'unidad' => $data['unidad'],
            'cantidad_contratada' => round((float) $data['cantidad_contratada'], 2),
            'precio_unitario' => round((float) $data['precio_unitario'], 2),
        ]);

        $this->guardarCantidades($frente, $rubro, $data);

        return redirect()->route('frentes.show', $frente);
    }

    public function update(Request $request, Rubro $rubro)
    {
        $data = $this->datos($request);
        $rubro->update([
            'descripcion' => $data['descripcion'],
            'unidad' => $data['unidad'],
            'cantidad_contratada' => round((float) $data['cantidad_contratada'], 2),
            'precio_unitario' => round((float) $data['precio_unitario'], 2),
        ]);
        $this->guardarCantidades($rubro->frente, $rubro, $data);

        return redirect()->route('frentes.show', $rubro->frente);
    }

    public function destroy(Rubro $rubro)
    {
        $frente = $rubro->frente;
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardarContratado(Rubro $rubro, array $data): void
    {
        $cambios = [];
        if (array_key_exists('cantidad_contratada', $data)) {
            $cambios['cantidad_contratada'] = round((float) ($data['cantidad_contratada'] ?? 0), 2);
        }
        if (array_key_exists('precio_unitario', $data)) {
            $cambios['precio_unitario'] = round((float) ($data['precio_unitario'] ?? 0), 2);
        }
        if ($cambios !== []) {
            $rubro->update($cambios);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardarCantidades(Frente $frente, Rubro $rubro, array $data): void
    {
        $planilla = $frente->contrato->planillas()->latest()->first();
        if (! $planilla) {
            return;
        }

        $ejecucion = $planilla->ejecuciones()->firstOrCreate(
            ['rubro_id' => $rubro->id],
            ['cantidad_anterior' => 0, 'cantidad_actual' => 0],
        );

        $cambios = ['cantidad_anterior' => round((float) ($data['cantidad_anterior'] ?? 0), 2)];
        $tieneHoja = $ejecucion->anexos()->whereHas('lineas')->exists();
        if (! $tieneHoja) {
            $cambios['cantidad_actual'] = round((float) ($data['cantidad_actual'] ?? 0), 2);
        }

        $ejecucion->update($cambios);
    }
}
