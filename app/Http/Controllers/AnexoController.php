<?php

namespace App\Http\Controllers;

use App\Models\AnexoImagen;
use App\Models\MedicionLinea;
use App\Models\PlanillaRubro;
use App\Services\PlanillaCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnexoController extends Controller
{
    public function show(PlanillaRubro $ejecucion)
    {
        $ejecucion->load(['planilla.contrato', 'rubro.frente', 'anexos.lineas', 'anexos.imagenes']);
        $anexo = $ejecucion->anexos()->firstOrCreate(
            ['hoja' => 1],
            ['tipo' => 'geometrico'],
        );
        $anexo->load(['lineas', 'imagenes']);

        return view('anexos.show', [
            'ejecucion' => $ejecucion,
            'anexo' => $anexo,
        ]);
    }

    public function guardar(Request $request, PlanillaRubro $ejecucion, PlanillaCalculator $calculator)
    {
        $lineas = $request->validate([
            'lineas' => ['nullable', 'array'],
            'lineas.*.descripcion' => ['nullable', 'string', 'max:255'],
            'lineas.*.base1' => ['nullable', 'numeric'],
            'lineas.*.base2' => ['nullable', 'numeric'],
            'lineas.*.altura' => ['nullable', 'numeric'],
            'lineas.*.numero' => ['nullable', 'numeric'],
            'lineas.*.total' => ['nullable', 'numeric'],
        ])['lineas'] ?? [];

        $anexo = $ejecucion->anexos()->firstOrCreate(['hoja' => 1], ['tipo' => 'geometrico']);
        $anexo->lineas()->delete();
        $orden = 1;

        foreach ($lineas as $linea) {
            $calculada = $this->subtotales($linea);
            $descripcion = trim((string) ($linea['descripcion'] ?? ''));
            if ($descripcion === '' && $calculada['total'] == 0.0) {
                continue;
            }
            $anexo->lineas()->create([
                'orden' => $orden++,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'base1' => $linea['base1'] ?? null,
                'base2' => $linea['base2'] ?? null,
                'altura' => $linea['altura'] ?? null,
                'numero' => $linea['numero'] ?? null,
                'longitud' => $calculada['longitud'],
                'area' => $calculada['area'],
                'volumen' => $calculada['volumen'],
                'total' => $calculada['total'],
            ]);
        }

        $calculator->sincronizarCantidadActual($ejecucion);

        return redirect()->route('anexos.show', $ejecucion)->with('estado', 'Mediciones guardadas.');
    }

    /**
     * @param  array<string, mixed>  $linea
     * @return array{longitud: ?float, area: ?float, volumen: ?float, total: float}
     */
    private function subtotales(array $linea): array
    {
        $base1 = $this->numero($linea['base1'] ?? null);
        $base2 = $this->numero($linea['base2'] ?? null);
        $altura = $this->numero($linea['altura'] ?? null);
        $numero = $this->numero($linea['numero'] ?? null);
        $factor = $numero ?? 1.0;

        $longitud = $base1 === null ? null : round($base1 * $factor, 2);
        $area = null;
        if ($base1 !== null && $base2 !== null) {
            $area = round($base1 * $base2 * $factor, 2);
        } elseif ($base1 !== null && $altura !== null) {
            $area = round($base1 * $altura * $factor, 2);
        }
        $volumen = ($base1 !== null && $base2 !== null && $altura !== null)
            ? round($base1 * $base2 * $altura * $factor, 2)
            : null;

        $total = ($linea['total'] ?? '') !== '' && $linea['total'] !== null
            ? (float) $linea['total']
            : ($volumen ?? $area ?? $longitud ?? $numero ?? 0.0);

        return [
            'longitud' => $longitud,
            'area' => $area,
            'volumen' => $volumen,
            'total' => round($total, 2),
        ];
    }

    private function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (float) $valor;
    }

    public function imagenes(Request $request, PlanillaRubro $ejecucion)
    {
        $data = $request->validate([
            'seccion' => ['required', 'in:inicial,otras'],
            'imagenes' => ['nullable', 'array'],
            'imagenes.*' => ['image', 'max:8192'],
        ]);

        $anexo = $ejecucion->anexos()->firstOrCreate(['hoja' => 1], ['tipo' => 'geometrico']);
        $carpeta = 'anexos/'.$data['seccion'];
        $orden = (int) $anexo->imagenes()
            ->where('ruta', 'like', $carpeta.'/%')
            ->max('orden');

        foreach ($request->file('imagenes', []) as $archivo) {
            $orden++;
            $anexo->imagenes()->create([
                'ruta' => $archivo->store($carpeta, 'public'),
                'orden' => $orden,
            ]);
        }

        return redirect()->route('anexos.show', $ejecucion)->with('estado', 'Imágenes guardadas.');
    }

    public function archivo(string $ruta)
    {
        $ruta = ltrim(str_replace('\\', '/', $ruta), '/');
        if ($ruta === '' || str_contains($ruta, '..') || ! Storage::disk('public')->exists($ruta)) {
            abort(404);
        }

        return Storage::disk('public')->response($ruta);
    }

    public function destroyImagen(PlanillaRubro $ejecucion, AnexoImagen $imagen)
    {
        abort_unless($imagen->anexo->planilla_rubro_id === $ejecucion->id, 404);
        Storage::disk('public')->delete($imagen->ruta);
        $imagen->delete();

        return redirect()->route('anexos.show', $ejecucion);
    }

    /**
     * @param  array<string, mixed>  $linea
     */
    private function totalLinea(array $linea): float
    {
        if (($linea['total'] ?? '') !== '' && $linea['total'] !== null) {
            return (float) $linea['total'];
        }

        $factores = array_values(array_filter(
            [$linea['base1'] ?? null, $linea['base2'] ?? null, $linea['altura'] ?? null, $linea['numero'] ?? null],
            fn ($valor) => $valor !== null && $valor !== ''
        ));

        if ($factores === []) {
            return 0.0;
        }

        return round(array_product(array_map('floatval', $factores)), 2);
    }

    public function store(Request $request, PlanillaRubro $ejecucion, PlanillaCalculator $calculator)
    {
        $data = $request->validate([
            'descripcion' => ['nullable', 'string', 'max:255'],
            'base1' => ['nullable', 'numeric'],
            'base2' => ['nullable', 'numeric'],
            'altura' => ['nullable', 'numeric'],
            'numero' => ['nullable', 'numeric'],
            'total' => ['required', 'numeric'],
        ]);

        $anexo = $ejecucion->anexos()->firstOrCreate(
            ['hoja' => 1],
            ['tipo' => 'geometrico'],
        );

        $anexo->lineas()->create([
            ...$data,
            'orden' => ($anexo->lineas()->max('orden') ?? 0) + 1,
        ]);

        $calculator->sincronizarCantidadActual($ejecucion);

        return redirect()->route('anexos.show', $ejecucion);
    }

    public function destroy(PlanillaRubro $ejecucion, MedicionLinea $linea, PlanillaCalculator $calculator)
    {
        abort_unless($linea->anexo->planilla_rubro_id === $ejecucion->id, 404);
        $linea->delete();
        $calculator->sincronizarCantidadActual($ejecucion);

        return redirect()->route('anexos.show', $ejecucion);
    }
}
