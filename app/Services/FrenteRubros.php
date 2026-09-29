<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Frente;
use App\Models\PlanillaRubro;
use App\Models\Rubro;

class FrenteRubros
{
    /**
     * @param  array<int, array<string, mixed>>  $filas
     */
    public function aplicarCatalogo(Contrato $contrato, array $filas, bool $reemplazarEnFrentes): void
    {
        $catalogo = $this->asegurarCatalogo($contrato);
        $siguente = (int) $catalogo->rubros()->max('numero');
        $numeros = [];

        foreach ($filas as $fila) {
            $descripcion = trim((string) ($fila['descripcion'] ?? ''));
            if ($descripcion === '') {
                continue;
            }

            $numero = (int) ($fila['numero'] ?? 0);
            if ($numero <= 0) {
                $numero = ++$siguente;
            }

            $numeros[] = $numero;
            $catalogo->rubros()->updateOrCreate(
                ['numero' => $numero],
                $this->definicion($fila, $descripcion),
            );
        }

        if ($numeros === []) {
            $catalogo->rubros()->delete();
        } else {
            $catalogo->rubros()->whereNotIn('numero', $numeros)->delete();
        }

        if (! $reemplazarEnFrentes) {
            return;
        }

        $catalogo->load('rubros');
        $planilla = $contrato->planillas()->latest('id')->first();
        $numerosCatalogo = $catalogo->rubros->pluck('numero')->all();

        foreach ($contrato->frentes as $frente) {
            foreach ($catalogo->rubros as $rubro) {
                $copia = $frente->rubros()->updateOrCreate(
                    ['numero' => $rubro->numero],
                    [
                        'codigo' => $rubro->codigo,
                        'descripcion' => $rubro->descripcion,
                        'unidad' => $rubro->unidad,
                        'cantidad_contratada' => $rubro->cantidad_contratada,
                        'precio_unitario' => $rubro->precio_unitario,
                        'tipo_hoja' => $rubro->tipo_hoja ?: 'valores',
                    ],
                );

                if ($planilla) {
                    $planilla->ejecuciones()->firstOrCreate(
                        ['rubro_id' => $copia->id],
                        ['cantidad_anterior' => 0, 'cantidad_actual' => 0],
                    );
                }
            }

            $sobrantes = $numerosCatalogo === []
                ? $frente->rubros()
                : $frente->rubros()->whereNotIn('numero', $numerosCatalogo);
            $sobrantes->delete();
        }
    }

    public function materializarFrentes(Contrato $contrato): void
    {
        $frentes = $contrato->frentes()->with('rubros')->orderBy('orden')->get();
        if ($frentes->isEmpty()) {
            return;
        }

        $unoPorFrente = $frentes->every(fn (Frente $frente) => $frente->rubros->count() <= 1);
        if ($unoPorFrente) {
            foreach ($frentes as $frente) {
                $rubro = $frente->rubros->first();
                if (! $rubro) {
                    continue;
                }
                $frente->update([
                    'nombre' => $rubro->descripcion,
                    'numero' => $rubro->numero,
                    'orden' => $rubro->numero,
                ]);
            }

            return;
        }

        if (! $this->rubrosCompartidos($contrato)) {
            return;
        }

        $conservados = [];
        foreach ($frentes as $frente) {
            foreach ($frente->rubros as $rubro) {
                $conservados[$rubro->numero] = $rubro;
            }
        }

        $nuevos = [];
        foreach ($conservados as $numero => $rubro) {
            $nuevo = $contrato->frentes()->create([
                'numero' => $numero,
                'nombre' => $rubro->descripcion,
                'orden' => $numero,
                'es_catalogo' => false,
            ]);
            $rubro->update(['frente_id' => $nuevo->id]);
            $nuevos[] = $nuevo->id;
        }

        Frente::query()
            ->where('contrato_id', $contrato->id)
            ->where('es_catalogo', false)
            ->whereNotIn('id', $nuevos)
            ->delete();
    }

    public function rubrosCompartidos(Contrato $contrato): bool
    {
        $conjuntos = $contrato->frentes()
            ->with('rubros')
            ->get()
            ->map(fn (Frente $frente) => $frente->rubros->pluck('numero')->sort()->values()->implode(','));

        return $conjuntos->unique()->count() <= 1;
    }

    public function clonarDesdeAnterior(Frente $nuevo): void
    {
        $contrato = $nuevo->contrato()->with('catalogo.rubros')->first();
        $planilla = $contrato->planillas()->latest('id')->first();
        if (! $planilla) {
            return;
        }

        $definiciones = $contrato->catalogo && $contrato->catalogo->rubros->isNotEmpty()
            ? $contrato->catalogo->rubros
            : Rubro::query()
                ->whereHas('frente', fn ($q) => $q->where('contrato_id', $contrato->id)->where('es_catalogo', false)->where('id', '!=', $nuevo->id))
                ->orderBy('numero')
                ->get()
                ->unique('numero')
                ->values();

        if ($definiciones->isEmpty()) {
            return;
        }

        $ultimoRubro = [];
        $anteriores = Frente::query()
            ->where('contrato_id', $contrato->id)
            ->where('id', '!=', $nuevo->id)
            ->where('es_catalogo', false)
            ->with('rubros')
            ->orderBy('orden')
            ->get();
        foreach ($anteriores as $frente) {
            foreach ($frente->rubros as $rubro) {
                $ultimoRubro[$rubro->numero] = $rubro->id;
            }
        }

        $ejecuciones = PlanillaRubro::query()
            ->where('planilla_id', $planilla->id)
            ->whereIn('rubro_id', array_values($ultimoRubro) ?: [0])
            ->get()
            ->keyBy('rubro_id');

        foreach ($definiciones as $rubro) {
            $copia = $nuevo->rubros()->create([
                'numero' => $rubro->numero,
                'codigo' => $rubro->codigo,
                'descripcion' => $rubro->descripcion,
                'unidad' => $rubro->unidad,
                'cantidad_contratada' => $rubro->cantidad_contratada,
                'precio_unitario' => $rubro->precio_unitario,
                'tipo_hoja' => $rubro->tipo_hoja ?: 'valores',
            ]);

            $origen = $ejecuciones->get($ultimoRubro[$rubro->numero] ?? 0);
            $acumulado = $origen
                ? round((float) $origen->cantidad_anterior + (float) $origen->cantidad_actual, 2)
                : 0;

            $planilla->ejecuciones()->create([
                'rubro_id' => $copia->id,
                'cantidad_anterior' => $acumulado,
                'cantidad_actual' => 0,
            ]);
        }
    }

    public function reflejarDefinicion(Rubro $rubro): void
    {
        $datos = [
            'codigo' => $rubro->codigo,
            'descripcion' => $rubro->descripcion,
            'unidad' => $rubro->unidad,
            'cantidad_contratada' => $rubro->cantidad_contratada,
            'precio_unitario' => $rubro->precio_unitario,
            'tipo_hoja' => $rubro->tipo_hoja ?: 'valores',
        ];

        foreach ($this->otrosFrentes($rubro->frente) as $frente) {
            $frente->rubros()->where('numero', $rubro->numero)->update($datos);
        }
    }

    public function reflejarNuevo(Rubro $rubro): void
    {
        $planilla = $rubro->frente->contrato->planillas()->latest('id')->first();

        foreach ($this->otrosFrentes($rubro->frente) as $frente) {
            if ($frente->rubros()->where('numero', $rubro->numero)->exists()) {
                continue;
            }

            $copia = $frente->rubros()->create([
                'numero' => $rubro->numero,
                'codigo' => $rubro->codigo,
                'descripcion' => $rubro->descripcion,
                'unidad' => $rubro->unidad,
                'cantidad_contratada' => $rubro->cantidad_contratada,
                'precio_unitario' => $rubro->precio_unitario,
                'tipo_hoja' => $rubro->tipo_hoja ?: 'valores',
            ]);

            if ($planilla) {
                $planilla->ejecuciones()->firstOrCreate(
                    ['rubro_id' => $copia->id],
                    ['cantidad_anterior' => 0, 'cantidad_actual' => 0],
                );
            }
        }
    }

    public function reflejarEliminacion(Frente $frente, int $numero): void
    {
        Rubro::query()
            ->where('numero', $numero)
            ->whereHas('frente', fn ($q) => $q->where('contrato_id', $frente->contrato_id))
            ->delete();
    }

    public function siguienteNumero(Frente $frente): int
    {
        return (int) Rubro::query()
            ->whereHas('frente', fn ($q) => $q->where('contrato_id', $frente->contrato_id))
            ->max('numero') + 1;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Frente>
     */
    private function otrosFrentes(Frente $frente)
    {
        return Frente::query()
            ->where('contrato_id', $frente->contrato_id)
            ->where('es_catalogo', false)
            ->where('id', '!=', $frente->id)
            ->get();
    }

    public function asegurarCatalogo(Contrato $contrato): Frente
    {
        $catalogo = $contrato->catalogo()->first();
        if ($catalogo) {
            return $catalogo;
        }

        return $contrato->catalogo()->create([
            'numero' => 0,
            'nombre' => 'Catálogo de rubros',
            'orden' => 0,
            'es_catalogo' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $fila
     * @return array<string, mixed>
     */
    private function definicion(array $fila, string $descripcion): array
    {
        return [
            'descripcion' => $descripcion,
            'unidad' => ($fila['unidad'] ?? '') !== '' ? $fila['unidad'] : 'u',
            'cantidad_contratada' => round((float) ($fila['cantidad_contratada'] ?? 0), 2),
            'precio_unitario' => round((float) ($fila['precio_unitario'] ?? 0), 2),
            'tipo_hoja' => ($fila['tipo_hoja'] ?? 'valores') === 'imagenes' ? 'imagenes' : 'valores',
        ];
    }
}
