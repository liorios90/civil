<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Services\FrenteRubros;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ContratoController extends Controller
{
    public function index()
    {
        return view('inicio', [
            'contratos' => Contrato::withCount('frentes')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('contratos.form', [
            'contrato' => new Contrato,
            'rubros' => collect(),
            'editarRubros' => true,
        ]);
    }

    public function store(Request $request, FrenteRubros $catalogo)
    {
        $contrato = Contrato::create($this->datos($request));
        $contrato->planillas()->create([
            'numero' => '01',
            'estado' => 'borrador',
            'iva_porcentaje' => 12,
        ]);
        $catalogo->aplicarCatalogo($contrato, $this->filas($request), true);

        return redirect()->route('contratos.show', $contrato)->with('estado', 'Contrato creado. Crea sus frentes generales.');
    }

    public function show(Contrato $contrato)
    {
        $contrato->load(['frentes.rubros', 'planillas']);
        $planilla = $contrato->planillas->sortByDesc('id')->first();

        return view('contratos.show', [
            'contrato' => $contrato,
            'planilla' => $planilla,
        ]);
    }

    public function edit(Contrato $contrato, FrenteRubros $catalogo)
    {
        $contrato->load('catalogo.rubros', 'frentes.rubros');
        $editarRubros = $contrato->catalogo !== null || $catalogo->rubrosCompartidos($contrato);

        return view('contratos.form', [
            'contrato' => $contrato,
            'rubros' => $this->rubrosEditables($contrato),
            'editarRubros' => $editarRubros,
        ]);
    }

    public function update(Request $request, Contrato $contrato, FrenteRubros $catalogo)
    {
        $contrato->update($this->datos($request));
        $puedeReemplazar = $contrato->catalogo()->exists() || $catalogo->rubrosCompartidos($contrato);
        if ($request->exists('filas') && $puedeReemplazar) {
            $catalogo->aplicarCatalogo($contrato, $this->filas($request), true);
        }

        return redirect()->route('contratos.show', $contrato)->with('estado', 'Contrato actualizado.');
    }

    public function destroy(Contrato $contrato)
    {
        $codigo = $contrato->codigo_proceso;
        $contrato->delete();

        return redirect()->route('inicio')->with('estado', 'Contrato '.$codigo.' eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Request $request): array
    {
        $data = $request->validate([
            'entidad' => ['nullable', 'string', 'max:255'],
            'codigo_proceso' => ['required', 'string', 'max:255'],
            'objeto' => ['nullable', 'string'],
            'contratista' => ['required', 'string', 'max:255'],
            'fiscalizador' => ['nullable', 'string', 'max:255'],
            'administrador' => ['nullable', 'string', 'max:255'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'provincia' => ['nullable', 'string', 'max:255'],
            'plazo' => ['nullable', 'string', 'max:255'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_termino' => ['nullable', 'date'],
            'monto_contrato' => ['nullable', 'numeric', 'min:0'],
            'porcentaje_anticipo' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['entidad'] = $data['entidad'] ?? '';
        $data['objeto'] = $data['objeto'] ?? '';
        $data['monto_contrato'] = $data['monto_contrato'] ?? 0;
        $anticipo = (float) ($data['porcentaje_anticipo'] ?? 0);
        $data['porcentaje_anticipo'] = $anticipo > 1 ? $anticipo / 100 : $anticipo;
        $data['anticipo'] = round($data['monto_contrato'] * $data['porcentaje_anticipo'], 2);
        $data['monto_contrato_iva'] = round($data['monto_contrato'] * 1.12, 2);

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filas(Request $request): array
    {
        return $request->validate([
            'filas' => ['nullable', 'array'],
            'filas.*.numero' => ['nullable', 'integer'],
            'filas.*.descripcion' => ['nullable', 'string'],
            'filas.*.unidad' => ['nullable', 'string', 'max:20'],
            'filas.*.cantidad_contratada' => ['nullable', 'numeric'],
            'filas.*.precio_unitario' => ['nullable', 'numeric'],
            'filas.*.tipo_hoja' => ['nullable', 'in:valores,imagenes'],
        ])['filas'] ?? [];
    }

    private function rubrosEditables(Contrato $contrato): Collection
    {
        if ($contrato->catalogo) {
            return $contrato->catalogo->rubros;
        }

        return $contrato->frentes->first()?->rubros ?? collect();
    }
}
