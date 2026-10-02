<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Rubro;
use App\Services\FrenteRubros;
use App\Services\PlanillaCalculator;
use App\Services\RubrosExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ContratoController extends Controller
{
    public function index(?PlanillaCalculator $calculator = null)
    {
        $calculator ??= app(PlanillaCalculator::class);
        $user = auth()->user();
        $consulta = Contrato::query()
            ->where('empresa_id', $user->empresa_id)
            ->with('planillas')
            ->withCount('frentes')
            ->latest();
        if ($user->esUsuario()) {
            $consulta->whereIn('id', $user->contratos()->select('contratos.id'));
        }
        $contratos = $consulta->get();

        return view('inicio', [
            'contratos' => $contratos,
            'tablero' => $user->esAdministrador() ? $this->tablero($contratos, $calculator) : null,
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
        $contrato = Contrato::create($this->datos($request) + [
            'empresa_id' => $request->user()->empresa_id,
        ]);
        $contrato->planillas()->create([
            'numero' => '01',
            'estado' => 'borrador',
            'iva_porcentaje' => 12,
        ]);
        $catalogo->aplicarCatalogo($contrato, $this->filasContrato($request), true);

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

    public function enviarAprobacion(Contrato $contrato)
    {
        $planilla = $this->planillaActual($contrato);
        abort_unless($planilla->estado === 'borrador', 404);
        $planilla->update(['estado' => 'pendiente']);

        return redirect()->route('contratos.show', $contrato)->with('estado', 'Planilla enviada a aprobación.');
    }

    public function aprobar(Contrato $contrato)
    {
        $planilla = $this->planillaActual($contrato);
        abort_unless($planilla->estado === 'pendiente', 404);
        $planilla->update(['estado' => 'aprobada']);

        return redirect()->route('contratos.show', $contrato)->with('estado', 'Planilla aprobada.');
    }

    public function devolver(Contrato $contrato)
    {
        $planilla = $this->planillaActual($contrato);
        abort_unless(in_array($planilla->estado, ['pendiente', 'aprobada'], true), 404);
        $planilla->update(['estado' => 'borrador']);

        return redirect()->route('contratos.show', $contrato)->with('estado', 'La planilla volvió a elaboración.');
    }

    public function enlace(Request $request, Contrato $contrato)
    {
        if (! $contrato->enlace_fiscalizador || $request->boolean('regenerar')) {
            $contrato->update(['enlace_fiscalizador' => Str::random(48)]);
        }

        return redirect()
            ->route('contratos.show', $contrato)
            ->with('estado', $request->boolean('regenerar')
                ? 'El enlace anterior ya no sirve. Use el nuevo.'
                : 'Enlace listo para enviar al fiscalizador.');
    }

    public function edit(Contrato $contrato)
    {
        $contrato->load('catalogo.rubros');

        return view('contratos.form', [
            'contrato' => $contrato,
            'rubros' => $contrato->catalogo?->rubros ?? collect(),
            'editarRubros' => true,
        ]);
    }

    public function update(Request $request, Contrato $contrato, FrenteRubros $catalogo)
    {
        $contrato->update($this->datos($request));
        if ($request->exists('filas') || $request->hasFile('rubros_excel')) {
            $catalogo->aplicarCatalogo($contrato, $this->filasContrato($request), false);
        }

        return redirect()->route('contratos.show', $contrato)->with('estado', 'Contrato actualizado.');
    }

    public function importarRubros(Request $request, Contrato $contrato, FrenteRubros $catalogo, RubrosExcel $lector)
    {
        $request->validate([
            'rubros_excel' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        $filas = $lector->leer($request->file('rubros_excel'));
        if ($filas === []) {
            return back()->withErrors([
                'rubros_excel' => 'El Excel no tiene rubros. La primera fila debe decir Descripción, Unidad y Precio unitario.',
            ]);
        }

        $catalogo->aplicarCatalogo($contrato, $filas, false);

        return redirect()->route('contratos.edit', $contrato)->with('estado', count($filas).' rubros cargados desde Excel. Pertenecen solo a este contrato.');
    }

    public function eliminarRubro(Contrato $contrato, Rubro $rubro)
    {
        $catalogo = $contrato->catalogo()->first();
        abort_unless($catalogo && $rubro->frente_id === $catalogo->id, 404);

        $rubro->delete();

        return redirect()->route('contratos.edit', $contrato)->with('estado', 'Rubro eliminado de este contrato. Las planillas que ya existen no cambian.');
    }

    public function destroy(Contrato $contrato)
    {
        $codigo = $contrato->codigo_proceso;
        $contrato->delete();

        return redirect()->route('inicio')->with('estado', 'Contrato '.$codigo.' eliminado.');
    }

    private function planillaActual(Contrato $contrato)
    {
        $planilla = $contrato->planillas()->latest('id')->first();
        abort_unless($planilla, 404);

        return $planilla;
    }

    /**
     * @param  Collection<int, Contrato>  $contratos
     * @return array{activas: int, avance: ?float, ejecutado: float, contratado: float, pendientes: array<int, array{contrato: Contrato, planilla: \App\Models\Planilla}>}
     */
    private function tablero(Collection $contratos, PlanillaCalculator $calculator): array
    {
        $contratado = 0.0;
        $ejecutado = 0.0;
        $activas = 0;
        $pendientes = [];

        foreach ($contratos as $contrato) {
            $planilla = $contrato->planillas->sortByDesc('id')->first();
            $estado = $planilla->estado ?? 'borrador';
            if ($estado !== 'aprobada') {
                $activas++;
            }
            if ($planilla && $estado === 'pendiente') {
                $pendientes[] = ['contrato' => $contrato, 'planilla' => $planilla];
            }
            if (! $planilla) {
                continue;
            }
            $totales = $calculator->liquidar($planilla)['totales'];
            $contratado += $totales['contratado'];
            $ejecutado += $totales['acumulado'];
        }

        return [
            'activas' => $activas,
            'avance' => $contratado > 0 ? round($ejecutado / $contratado * 100, 2) : null,
            'ejecutado' => round($ejecutado, 2),
            'contratado' => round($contratado, 2),
            'pendientes' => $pendientes,
        ];
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
    /**
     * @return array<int, array<string, mixed>>
     */
    private function filasContrato(Request $request): array
    {
        if ($request->hasFile('rubros_excel')) {
            $request->validate([
                'rubros_excel' => ['file', 'mimes:xlsx,xls', 'max:5120'],
            ]);
            $filas = app(RubrosExcel::class)->leer($request->file('rubros_excel'));
            if ($filas !== []) {
                return $filas;
            }
        }

        return $this->filas($request);
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
