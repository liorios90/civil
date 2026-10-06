<?php

namespace App\Http\Controllers;

use App\Models\CatalogoRubro;
use App\Services\RubrosExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    public function edit(Request $request)
    {
        return view('catalogo.edit', [
            'rubros' => $this->rubros($request),
        ]);
    }

    public function update(Request $request, RubrosExcel $lector)
    {
        $filas = $this->filas($request, $lector);
        $this->guardar($request, $filas);

        return redirect()->route('catalogo.edit')->with('estado', 'Catálogo general guardado.');
    }

    public function excel(Request $request, RubrosExcel $lector)
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

        $this->guardar($request, $filas);

        return redirect()->route('catalogo.edit')->with('estado', count($filas).' rubros cargados en el catálogo general.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $filas
     */
    private function guardar(Request $request, array $filas): void
    {
        $empresaId = (int) $request->user()->empresa_id;
        abort_unless($empresaId > 0, 404);

        DB::transaction(function () use ($empresaId, $filas) {
            CatalogoRubro::query()->where('empresa_id', $empresaId)->delete();
            $usados = [];
            $siguiente = 1;
            foreach ($filas as $fila) {
                $descripcion = trim((string) ($fila['descripcion'] ?? ''));
                if ($descripcion === '') {
                    continue;
                }
                $numero = (int) ($fila['numero'] ?? 0);
                if ($numero <= 0 || isset($usados[$numero])) {
                    while (isset($usados[$siguiente])) {
                        $siguiente++;
                    }
                    $numero = $siguiente;
                }
                $usados[$numero] = true;
                $siguiente = max($siguiente, $numero + 1);
                CatalogoRubro::query()->create([
                    'empresa_id' => $empresaId,
                    'numero' => $numero,
                    'descripcion' => $descripcion,
                    'unidad' => mb_substr(trim((string) ($fila['unidad'] ?? '')) ?: 'u', 0, 20),
                    'cantidad_contratada' => $fila['cantidad_contratada'] ?? 0,
                    'precio_unitario' => $fila['precio_unitario'] ?? 0,
                ]);
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filas(Request $request, RubrosExcel $lector): array
    {
        if ($request->hasFile('rubros_excel')) {
            $request->validate([
                'rubros_excel' => ['file', 'mimes:xlsx,xls', 'max:5120'],
            ]);
            $filas = $lector->leer($request->file('rubros_excel'));
            if ($filas !== []) {
                return $filas;
            }
        }

        return $request->validate([
            'filas' => ['nullable', 'array'],
            'filas.*.numero' => ['nullable', 'integer'],
            'filas.*.descripcion' => ['nullable', 'string'],
            'filas.*.unidad' => ['nullable', 'string', 'max:20'],
            'filas.*.cantidad_contratada' => ['nullable', 'numeric'],
            'filas.*.precio_unitario' => ['nullable', 'numeric'],
        ])['filas'] ?? [];
    }

    private function rubros(Request $request)
    {
        $empresaId = (int) $request->user()->empresa_id;
        abort_unless($empresaId > 0, 404);

        return CatalogoRubro::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('numero')
            ->get();
    }
}
