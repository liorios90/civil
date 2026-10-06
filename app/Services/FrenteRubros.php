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

        $quitados = $numeros === []
            ? $catalogo->rubros()
            : $catalogo->rubros()->whereNotIn('numero', $numeros);
        $quitados->get()->each->delete();

        if (! $reemplazarEnFrentes) {
            return;
        }

        $catalogo->load('rubros');
        $planilla = $contrato->planillas()->latest('id')->first();
        $paraPlanillas = $catalogo->rubros
            ->filter(fn (Rubro $rubro) => (float) $rubro->cantidad_contratada > 0)
            ->values();
        $numerosPlanilla = $paraPlanillas->pluck('numero')->all();

        foreach ($contrato->frentes as $frente) {
            foreach ($paraPlanillas as $rubro) {
                $copia = $frente->rubros()->updateOrCreate(
                    ['numero' => $rubro->numero],
                    [
                        'codigo' => $rubro->codigo,
                        'descripcion' => $rubro->descripcion,
                        'unidad' => $rubro->unidad,
                        'cantidad_contratada' => $rubro->cantidad_contratada,
                        'precio_unitario' => $rubro->precio_unitario,
                        'medicion' => $rubro->medicion,
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

            $sobrantes = $numerosPlanilla === []
                ? $frente->rubros()
                : $frente->rubros()->whereNotIn('numero', $numerosPlanilla);
            $sobrantes->get()->each->delete();
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

        $anterior = Frente::query()
            ->where('contrato_id', $contrato->id)
            ->where('es_catalogo', false)
            ->where('id', '!=', $nuevo->id)
            ->where('orden', '<', $nuevo->orden)
            ->orderByDesc('orden')
            ->orderByDesc('id')
            ->with(['rubros.planillaRubros' => fn ($consulta) => $consulta->where('planilla_id', $planilla->id)->with('anexos')])
            ->first();

        if ($anterior && $anterior->rubros->isNotEmpty()) {
            $definiciones = $anterior->rubros;
        } elseif ($contrato->catalogo && $contrato->catalogo->rubros->isNotEmpty()) {
            $definiciones = $contrato->catalogo->rubros
                ->filter(fn (Rubro $rubro) => (float) $rubro->cantidad_contratada > 0)
                ->values();
        } else {
            $definiciones = Rubro::query()
                ->whereHas('frente', fn ($q) => $q->where('contrato_id', $contrato->id)->where('es_catalogo', false)->where('id', '!=', $nuevo->id))
                ->orderBy('numero')
                ->get()
                ->unique('numero')
                ->values();
        }

        if ($definiciones->isEmpty()) {
            return;
        }

        foreach ($definiciones as $rubro) {
            $medicion = $this->medicionDesdeRubro($rubro);
            $copia = $nuevo->rubros()->create([
                'numero' => $rubro->numero,
                'codigo' => $rubro->codigo,
                'descripcion' => $rubro->descripcion,
                'unidad' => $rubro->unidad,
                'cantidad_contratada' => $rubro->cantidad_contratada,
                'precio_unitario' => $rubro->precio_unitario,
                'medicion' => $medicion,
                'tipo_hoja' => $rubro->tipo_hoja ?: 'valores',
            ]);

            $ejecucion = $planilla->ejecuciones()->create([
                'rubro_id' => $copia->id,
                'cantidad_anterior' => 0,
                'cantidad_actual' => 0,
            ]);

            $this->copiarEstructuraMedicion($rubro, $ejecucion, $medicion);
        }

        $this->arrastrarTotal($nuevo);
    }

    /**
     * @return array<int, array{etiqueta: string, formula: string}>|null
     */
    private function medicionDesdeRubro(Rubro $rubro): ?array
    {
        $anexo = $rubro->planillaRubros->first()?->anexos->sortBy('hoja')->first();
        if ($anexo && is_array($anexo->columnas) && $anexo->columnas !== []) {
            $datos = [];
            foreach ($anexo->columnas as $columna) {
                if (! is_array($columna) || ($columna['clave'] ?? '') === 'descripcion') {
                    continue;
                }
                $etiqueta = trim((string) ($columna['etiqueta'] ?? ''));
                if ($etiqueta === '') {
                    continue;
                }
                $formula = trim((string) ($columna['formula'] ?? ''));
                if ($formula !== '' && ! str_starts_with($formula, '=')) {
                    $formula = '='.$formula;
                }
                $datos[] = [
                    'etiqueta' => mb_substr($etiqueta, 0, 40),
                    'formula' => mb_substr($formula, 0, 200),
                ];
            }
            if ($datos !== []) {
                return $datos;
            }
        }

        return $this->medicionCopiada($rubro->medicion);
    }

    /**
     * @param  array<int, array{etiqueta: string, formula: string}>|null  $medicion
     */
    private function copiarEstructuraMedicion(Rubro $origen, PlanillaRubro $ejecucion, ?array $medicion): void
    {
        $anexoOrigen = $origen->planillaRubros->first()?->anexos->sortBy('hoja')->first();
        $columnas = null;
        $tipo = 'geometrico';

        if ($anexoOrigen) {
            $tipo = $anexoOrigen->tipo ?: 'geometrico';
            if (is_array($anexoOrigen->columnas) && $anexoOrigen->columnas !== []) {
                $columnas = $anexoOrigen->columnas;
            }
        }

        if ($columnas === null && $medicion !== null) {
            $columnas = HojaCalculo::columnasDesdeMedicion($medicion);
        }

        if ($columnas === null || $columnas === []) {
            return;
        }

        $ejecucion->anexos()->create([
            'tipo' => $tipo,
            'hoja' => 1,
            'columnas' => $columnas,
        ]);
    }

    public function arrastrarTotal(Frente $nuevo): void
    {
        $nuevo->unsetRelation('rubros');
        $planilla = $nuevo->contrato()->first()?->planillas()->latest('id')->first();
        $anterior = Frente::query()
            ->where('contrato_id', $nuevo->contrato_id)
            ->where('es_catalogo', false)
            ->where('id', '!=', $nuevo->id)
            ->where('orden', '<', $nuevo->orden)
            ->orderByDesc('orden')
            ->with('rubros')
            ->first();
        if (! $planilla || ! $anterior) {
            return;
        }

        $porNumero = $anterior->rubros->keyBy('numero');
        $ejecuciones = PlanillaRubro::query()
            ->where('planilla_id', $planilla->id)
            ->whereIn('rubro_id', $anterior->rubros->pluck('id')->merge($nuevo->rubros->pluck('id')))
            ->get()
            ->keyBy('rubro_id');

        foreach ($nuevo->rubros as $rubro) {
            $origenRubro = $porNumero->get($rubro->numero);
            $origen = $origenRubro ? $ejecuciones->get($origenRubro->id) : null;
            $valor = round((float) ($origen->cantidad_anterior ?? 0) + (float) ($origen->cantidad_actual ?? 0), 2);
            $fila = $ejecuciones->get($rubro->id);
            if (! $fila) {
                $planilla->ejecuciones()->create([
                    'rubro_id' => $rubro->id,
                    'cantidad_anterior' => $valor,
                    'cantidad_actual' => 0,
                ]);
                continue;
            }
            if (round((float) $fila->cantidad_anterior, 2) !== $valor) {
                $fila->update(['cantidad_anterior' => $valor]);
            }
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
            'medicion' => $rubro->medicion,
            'tipo_hoja' => $rubro->tipo_hoja ?: 'valores',
        ];

        foreach ($this->otrosFrentes($rubro->frente) as $frente) {
            $frente->rubros()->where('numero', $rubro->numero)->update($datos);
        }
    }

    public function reflejarNuevo(Rubro $rubro): void
    {
        $this->asegurarCatalogo($rubro->frente->contrato)->rubros()->updateOrCreate(
            ['numero' => $rubro->numero],
            [
                'codigo' => $rubro->codigo,
                'descripcion' => $rubro->descripcion,
                'unidad' => $rubro->unidad,
                'cantidad_contratada' => $rubro->cantidad_contratada,
                'precio_unitario' => $rubro->precio_unitario,
                'medicion' => $rubro->medicion,
                'tipo_hoja' => $rubro->tipo_hoja ?: 'valores',
            ],
        );
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

    /**
     * @return array<int, array{etiqueta: string, formula: string}>|null
     */
    private function medicionCopiada(mixed $medicion): ?array
    {
        if (! is_array($medicion)) {
            return null;
        }

        $datos = [];
        foreach ($medicion as $dato) {
            if (! is_array($dato)) {
                continue;
            }
            $etiqueta = trim((string) ($dato['etiqueta'] ?? ''));
            $formula = trim((string) ($dato['formula'] ?? ''));
            if ($etiqueta === '' && $formula === '') {
                continue;
            }
            if ($formula === '' && str_starts_with($etiqueta, '=')) {
                $formula = $etiqueta;
                $etiqueta = 'Total';
            }
            if ($etiqueta === '') {
                $etiqueta = 'Total';
            }
            if ($formula !== '' && ! str_starts_with($formula, '=')) {
                $formula = '='.$formula;
            }
            $datos[] = [
                'etiqueta' => mb_substr($etiqueta, 0, 40),
                'formula' => mb_substr($formula, 0, 200),
            ];
            if (count($datos) >= 16) {
                break;
            }
        }

        return $datos === [] ? null : $datos;
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
        $datos = [
            'descripcion' => $descripcion,
            'unidad' => ($fila['unidad'] ?? '') !== '' ? $fila['unidad'] : 'u',
            'cantidad_contratada' => round((float) ($fila['cantidad_contratada'] ?? 0), 2),
            'precio_unitario' => round((float) ($fila['precio_unitario'] ?? 0), 2),
            'tipo_hoja' => ($fila['tipo_hoja'] ?? 'valores') === 'imagenes' ? 'imagenes' : 'valores',
        ];
        if (array_key_exists('medicion', $fila)) {
            $datos['medicion'] = $this->medicionCopiada($fila['medicion'] ?? null);
        }

        return $datos;
    }
}
