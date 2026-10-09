<?php

namespace App\Http\Controllers;

use App\Models\Anexo;
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
    private function asegurarEditable(PlanillaRubro $ejecucion): void
    {
        $ejecucion->loadMissing('rubro.frente');
        $ejecucion->rubro->frente->asegurarEditable();
    }

    public function show(PlanillaRubro $ejecucion)
    {
        $ejecucion->load(['planilla.contrato', 'rubro.frente', 'anexos.lineas', 'anexos.imagenes']);
        $frente = $ejecucion->rubro->frente;
        $editable = $frente->esUltimaPlanilla();
        if ($editable) {
            $ejecucion->anexos()->firstOrCreate(['hoja' => 1], ['tipo' => 'geometrico']);
        }
        $anexos = $ejecucion->anexos()->with(['lineas', 'imagenes'])->orderBy('hoja')->get();
        if ($anexos->isEmpty()) {
            $vacio = $ejecucion->anexos()->make(['hoja' => 1, 'tipo' => 'geometrico']);
            $vacio->setRelation('lineas', collect());
            $vacio->setRelation('imagenes', collect());
            $anexos = collect([$vacio]);
        }

        return view('anexos.show', [
            'ejecucion' => $ejecucion,
            'anexo' => $anexos->first(),
            'anexos' => $anexos,
            'periodoAbierto' => $editable,
        ]);
    }

    public function subtotal(PlanillaRubro $ejecucion)
    {
        $this->asegurarEditable($ejecucion);
        $ejecucion->anexos()->firstOrCreate(['hoja' => 1], ['tipo' => 'geometrico']);
        $siguiente = ((int) $ejecucion->anexos()->max('hoja')) + 1;
        $ejecucion->anexos()->create([
            'hoja' => $siguiente,
            'tipo' => 'geometrico',
            'columnas' => HojaCalculo::columnasExcel(),
        ]);

        return redirect()->route('anexos.show', $ejecucion)->with('estado', 'Tabla de subtotal agregada.');
    }

    public function destroySubtotal(PlanillaRubro $ejecucion, Anexo $anexo, PlanillaCalculator $calculator)
    {
        $this->asegurarEditable($ejecucion);
        abort_unless($anexo->planilla_rubro_id === $ejecucion->id && (int) $anexo->hoja > 1, 404);
        $anexo->delete();
        $calculator->sincronizarCantidadActual($ejecucion);

        return redirect()->route('anexos.show', $ejecucion)->with('estado', 'Tabla de subtotal quitada.');
    }

    public function guardar(Request $request, PlanillaRubro $ejecucion, PlanillaCalculator $calculator)
    {
        $this->asegurarEditable($ejecucion);
        $data = $request->validate([
            'tablas' => ['required', 'array'],
            'tablas.*.orden_columnas' => ['nullable', 'array', 'max:24'],
            'tablas.*.orden_columnas.*' => ['required', 'string', 'max:31'],
            'tablas.*.etiquetas' => ['nullable', 'array'],
            'tablas.*.etiquetas.*' => ['nullable', 'string', 'max:40'],
            'tablas.*.formulas' => ['nullable', 'array'],
            'tablas.*.formulas.*' => ['nullable', 'string', 'max:200'],
            'tablas.*.lineas' => ['nullable', 'array', 'max:200'],
            'tablas.*.lineas.*.celdas' => ['nullable', 'array'],
            'tablas.*.lineas.*.celdas.*' => ['nullable', 'string', 'max:500'],
        ]);

        $tipo = UnidadMedicion::tipo($ejecucion->rubro->unidad);
        foreach ($data['tablas'] as $id => $tabla) {
            $anexo = $ejecucion->anexos()->whereKey($id)->firstOrFail();
            $this->guardarTabla($anexo, $tabla, $tipo);
        }

        $calculator->sincronizarCantidadActual($ejecucion);

        return redirect()->route('anexos.show', $ejecucion)->with('estado', 'Mediciones guardadas.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardarTabla(Anexo $anexo, array $data, string $tipo): void
    {
        $columnas = HojaCalculo::normalizar($data['orden_columnas'] ?? null, $data['etiquetas'] ?? [], $tipo, $data['formulas'] ?? []);
        $claves = array_column($columnas, 'clave');
        $ultima = (string) ($claves[array_key_last($claves)] ?? 'total');
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
                if ($texto !== '' && ! str_starts_with($texto, '=')) {
                    $ultimo = $indice;
                    break;
                }
            }
        }
        $crudas = array_slice($crudas, 0, $ultimo + 1);
        $resueltas = HojaCalculo::resolver($crudas, $columnas, $tipo);

        $anexo->update(['columnas' => $columnas]);
        $existentes = $anexo->lineas()->get()->values();
        $orden = 1;

        foreach ($resueltas as $indice => $resuelta) {
            $textoUltima = trim((string) ($crudas[$indice][$ultima] ?? ''));
            $cantidad = $textoUltima === '' ? 0.0 : round((float) ($resuelta['numeros'][$ultima] ?? 0), 2);
            $valores = [
                'orden' => $orden,
                'descripcion' => $resuelta['descripcion'] !== '' ? $resuelta['descripcion'] : null,
                'celdas' => $resuelta['celdas'] !== [] ? $resuelta['celdas'] : null,
                'total' => $cantidad,
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
        $this->asegurarEditable($ejecucion);
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
        $this->asegurarEditable($ejecucion);
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
        $this->asegurarEditable($ejecucion);
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
        $this->asegurarEditable($ejecucion);
        abort_unless($linea->anexo->planilla_rubro_id === $ejecucion->id, 404);
        $linea->delete();
        $calculator->sincronizarCantidadActual($ejecucion);

        return redirect()->route('anexos.show', $ejecucion);
    }
}
