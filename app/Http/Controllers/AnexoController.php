<?php

namespace App\Http\Controllers;

use App\Models\AnexoImagen;
use App\Models\MedicionLinea;
use App\Models\PlanillaRubro;
use App\Services\PlanillaCalculator;
use App\Services\UnidadMedicion;
use Illuminate\Http\RedirectResponse;
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
        if ($respuesta = $this->rechazarSiCerrada($ejecucion)) {
            return $respuesta;
        }

        $lineas = $request->validate([
            'lineas' => ['nullable', 'array'],
            'lineas.*.descripcion' => ['nullable', 'string', 'max:255'],
            'lineas.*.base1' => ['nullable', 'numeric'],
            'lineas.*.base2' => ['nullable', 'numeric'],
            'lineas.*.altura' => ['nullable', 'numeric'],
            'lineas.*.numero' => ['nullable', 'numeric'],
            'lineas.*.longitud' => ['nullable', 'numeric'],
            'lineas.*.area' => ['nullable', 'numeric'],
            'lineas.*.volumen' => ['nullable', 'numeric'],
            'lineas.*.total' => ['nullable', 'numeric'],
            'lineas.*.manual_total' => ['nullable', 'in:0,1'],
        ])['lineas'] ?? [];

        $tipo = UnidadMedicion::tipo($ejecucion->rubro->unidad);
        $anexo = $ejecucion->anexos()->firstOrCreate(['hoja' => 1], ['tipo' => 'geometrico']);
        $existentes = $anexo->lineas()->get()->values();
        $orden = 1;

        foreach ($lineas as $linea) {
            $calculada = $this->subtotales($linea, $tipo);
            $descripcion = trim((string) ($linea['descripcion'] ?? ''));
            if ($descripcion === '' && $calculada['total'] == 0.0) {
                continue;
            }
            $valores = [
                'orden' => $orden,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'base1' => $linea['base1'] ?? null,
                'base2' => $linea['base2'] ?? null,
                'altura' => $linea['altura'] ?? null,
                'numero' => $linea['numero'] ?? null,
                'longitud' => $calculada['longitud'],
                'area' => $calculada['area'],
                'volumen' => $calculada['volumen'],
                'total' => $calculada['total'],
            ];
            $actual = $existentes->get($orden - 1);
            if ($actual) {
                $actual->setRelation('anexo', $anexo)->fill($valores)->save();
            } else {
                $anexo->lineas()->create($valores);
            }
            $orden++;
        }

        $existentes->slice($orden - 1)->each(fn (MedicionLinea $sobrante) => $sobrante->setRelation('anexo', $anexo)->delete());

        $calculator->sincronizarCantidadActual($ejecucion);

        return redirect()->route('anexos.show', $ejecucion)->with('estado', 'Mediciones guardadas.');
    }

    /**
     * @param  array<string, mixed>  $linea
     * @return array{longitud: ?float, area: ?float, volumen: ?float, total: float}
     */
    private function subtotales(array $linea, string $tipo): array
    {
        $calculada = UnidadMedicion::desdeDimensiones($linea, $tipo);
        $longitud = $this->numero($linea['longitud'] ?? null) ?? $calculada['longitud'];
        $area = $this->numero($linea['area'] ?? null) ?? $calculada['area'];
        $volumen = $this->numero($linea['volumen'] ?? null) ?? $calculada['volumen'];
        $numero = $this->numero($linea['numero'] ?? null);
        $sugerido = UnidadMedicion::total($tipo, $longitud, $area, $volumen, $numero);
        $escrito = $this->numero($linea['total'] ?? null);

        if (($linea['manual_total'] ?? '') === '1') {
            $total = round((float) ($escrito ?? 0), 2);
        } elseif ($tipo === UnidadMedicion::KILOGRAMO || $tipo === UnidadMedicion::NUMERO) {
            $total = round((float) ($escrito ?? $sugerido ?? 0), 2);
        } else {
            $total = round((float) ($sugerido ?? $escrito ?? 0), 2);
        }

        return [
            'longitud' => $longitud,
            'area' => $area,
            'volumen' => $volumen,
            'total' => $total,
        ];
    }

    private function rechazarSiCerrada(PlanillaRubro $ejecucion): ?RedirectResponse
    {
        $ejecucion->loadMissing('planilla');
        if (($ejecucion->planilla->estado ?: 'borrador') === 'borrador') {
            return null;
        }

        return redirect()
            ->route('anexos.show', $ejecucion)
            ->with('estado', 'Esta planilla ya no está en elaboración. Las mediciones no cambian.');
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
        if ($respuesta = $this->rechazarSiCerrada($ejecucion)) {
            return $respuesta;
        }

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
        if ($respuesta = $this->rechazarSiCerrada($ejecucion)) {
            return $respuesta;
        }

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
        if ($respuesta = $this->rechazarSiCerrada($ejecucion)) {
            return $respuesta;
        }

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
        if ($respuesta = $this->rechazarSiCerrada($ejecucion)) {
            return $respuesta;
        }

        abort_unless($linea->anexo->planilla_rubro_id === $ejecucion->id, 404);
        $linea->delete();
        $calculator->sincronizarCantidadActual($ejecucion);

        return redirect()->route('anexos.show', $ejecucion);
    }
}
