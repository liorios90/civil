<?php

namespace App\Http\Controllers;

use App\Models\AnexoImagen;
use App\Models\MedicionLinea;
use App\Models\PlanillaRubro;
use App\Services\HojaCalculo;
use App\Services\PlanillaCalculator;
use App\Services\UnidadMedicion;
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
        $data = $request->validate([
            'orden_columnas' => ['nullable', 'array', 'max:24'],
            'orden_columnas.*' => ['required', 'string', 'max:31'],
            'etiquetas' => ['nullable', 'array'],
            'etiquetas.*' => ['nullable', 'string', 'max:40'],
            'lineas' => ['nullable', 'array', 'max:200'],
            'lineas.*.celdas' => ['nullable', 'array'],
            'lineas.*.celdas.*' => ['nullable', 'string', 'max:500'],
        ]);

        $tipo = UnidadMedicion::tipo($ejecucion->rubro->unidad);
        $columnas = HojaCalculo::normalizar($data['orden_columnas'] ?? null, $data['etiquetas'] ?? [], $tipo);
        $claves = array_column($columnas, 'clave');
        $crudas = [];
        foreach ($data['lineas'] ?? [] as $linea) {
            $fila = [];
            foreach ($claves as $clave) {
                $fila[$clave] = trim((string) ($linea['celdas'][$clave] ?? ''));
            }
            $crudas[] = $fila;
        }
        $ultimo = -1;
        foreach ($crudas as $indice => $fila) {
            foreach ($fila as $texto) {
                if ($texto !== '') {
                    $ultimo = $indice;
                    break;
                }
            }
        }
        $crudas = array_slice($crudas, 0, $ultimo + 1);
        $resueltas = HojaCalculo::resolver($crudas, $columnas, $tipo);

        $anexo = $ejecucion->anexos()->firstOrCreate(['hoja' => 1], ['tipo' => 'geometrico']);
        $anexo->update(['columnas' => $columnas]);
        $existentes = $anexo->lineas()->get()->values();
        $orden = 1;

        foreach ($resueltas as $resuelta) {
            $valores = [
                'orden' => $orden,
                'descripcion' => $resuelta['descripcion'] !== '' ? $resuelta['descripcion'] : null,
                'celdas' => $resuelta['celdas'] !== [] ? $resuelta['celdas'] : null,
                'total' => $resuelta['numeros']['total'] ?? 0,
            ];
            foreach (['base1', 'base2', 'altura', 'numero', 'longitud', 'area', 'volumen'] as $campo) {
                $valores[$campo] = $resuelta['numeros'][$campo] ?? null;
            }
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
