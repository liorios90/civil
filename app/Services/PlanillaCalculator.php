<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Planilla;
use App\Models\PlanillaRubro;
use App\Models\Rubro;

class PlanillaCalculator
{
    /**
     * @return array<string, float|string|null>
     */
    public function linea(Rubro $rubro, float $anterior, float $actual): array
    {
        $contratada = round((float) $rubro->cantidad_contratada, 2);
        $unitario = round((float) $rubro->precio_unitario, 2);
        $anterior = round($anterior, 2);
        $actual = round($actual, 2);
        $cantidadTotal = round($anterior + $actual, 2);
        $valorAnterior = round($anterior * $unitario, 2);
        $valorActual = round($actual * $unitario, 2);
        $valorTotal = round($valorAnterior + $valorActual, 2);
        $totalContratado = round($contratada * $unitario, 2);

        $incrementoCantidad = $cantidadTotal > $contratada ? round($cantidadTotal - $contratada, 2) : null;
        $decrementoCantidad = $cantidadTotal < $contratada ? round($cantidadTotal - $contratada, 2) : null;

        return [
            'cantidad_contratada' => $contratada,
            'precio_unitario' => $unitario,
            'total_contratado' => $totalContratado,
            'cantidad_anterior' => $anterior,
            'cantidad_actual' => $actual,
            'cantidad_total' => $cantidadTotal,
            'valor_anterior' => $valorAnterior,
            'valor_actual' => $valorActual,
            'valor_total' => $valorTotal,
            'incremento_cantidad' => $incrementoCantidad,
            'incremento_valor' => $incrementoCantidad === null ? null : round($incrementoCantidad * $unitario, 2),
            'decremento_cantidad' => $decrementoCantidad,
            'decremento_valor' => $decrementoCantidad === null ? null : round($decrementoCantidad * $unitario, 2),
            'diferencia_cantidad' => round($cantidadTotal - $contratada, 2),
            'diferencia_valor' => round(($cantidadTotal - $contratada) * $unitario, 2),
            'porcentaje' => $totalContratado == 0.0 ? null : round($valorTotal / $totalContratado, 4),
            'observacion' => $cantidadTotal > $contratada ? 'INCREMENTO DE CANTIDADES' : '',
        ];
    }

    /**
     * En la planilla nueva, el anterior de cada rubro es el total
     * (anterior + actual) de ese rubro en la planilla anterior.
     */
    public function anteriorPagado(Planilla $planilla, int $rubroId): float
    {
        $previa = Planilla::query()
            ->where('contrato_id', $planilla->contrato_id)
            ->where('id', '<', $planilla->id)
            ->orderByDesc('id')
            ->first();

        if ($previa) {
            $origen = PlanillaRubro::query()
                ->where('planilla_id', $previa->id)
                ->where('rubro_id', $rubroId)
                ->first();
            if ($origen) {
                return round((float) $origen->cantidad_anterior + (float) $origen->cantidad_actual, 2);
            }
        }

        $saldo = PlanillaRubro::query()
            ->where('planilla_id', $planilla->id)
            ->where('rubro_id', $rubroId)
            ->value('cantidad_anterior');

        return round((float) $saldo, 2);
    }

    public function fijarAnteriores(Planilla $planilla): void
    {
        $planilla->load('ejecuciones');
        foreach ($planilla->ejecuciones as $ejecucion) {
            $anterior = $this->anteriorPagado($planilla, $ejecucion->rubro_id);
            if (round((float) $ejecucion->cantidad_anterior, 2) !== $anterior) {
                $ejecucion->update(['cantidad_anterior' => $anterior]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function liquidar(Planilla $planilla): array
    {
        $planilla->loadMissing(['contrato', 'ejecuciones.rubro.frente']);

        $frentes = [];
        $totales = [
            'contratado' => 0.0,
            'anterior' => 0.0,
            'actual' => 0.0,
            'acumulado' => 0.0,
        ];

        foreach ($planilla->ejecuciones as $ejecucion) {
            $rubro = $ejecucion->rubro;
            $frente = $rubro->frente;
            if ($frente->es_catalogo) {
                continue;
            }
            $linea = $this->linea(
                $rubro,
                (float) $ejecucion->cantidad_anterior,
                (float) $ejecucion->cantidad_actual,
            );

            if (! isset($frentes[$frente->id])) {
                $frentes[$frente->id] = [
                    'frente' => $frente,
                    'lineas' => [],
                    'subtotal' => ['contratado' => 0.0, 'anterior' => 0.0, 'actual' => 0.0, 'acumulado' => 0.0],
                ];
            }

            $frentes[$frente->id]['lineas'][] = [
                'ejecucion' => $ejecucion,
                'rubro' => $rubro,
                'calculo' => $linea,
            ];

            foreach (['contratado' => 'total_contratado', 'anterior' => 'valor_anterior', 'actual' => 'valor_actual', 'acumulado' => 'valor_total'] as $destino => $origen) {
                $frentes[$frente->id]['subtotal'][$destino] += $linea[$origen];
                $totales[$destino] += $linea[$origen];
            }
        }

        foreach ($frentes as &$frente) {
            foreach ($frente['subtotal'] as $clave => $valor) {
                $frente['subtotal'][$clave] = round($valor, 2);
            }
        }
        unset($frente);

        foreach ($totales as $clave => $valor) {
            $totales[$clave] = round($valor, 2);
        }

        $porcentajeAnticipo = (float) $planilla->contrato->porcentaje_anticipo;
        $ivaTasa = (float) $planilla->iva_porcentaje / 100;
        $anticipo = round($totales['contratado'] * $porcentajeAnticipo, 2);

        $iva = [
            'anterior' => round($totales['anterior'] * $ivaTasa, 2),
            'actual' => round($totales['actual'] * $ivaTasa, 2),
            'acumulado' => round($totales['acumulado'] * $ivaTasa, 2),
        ];

        $amortizacion = [
            'anterior' => round($totales['anterior'] * $porcentajeAnticipo, 2),
            'actual' => round($totales['actual'] * $porcentajeAnticipo, 2),
            'acumulado' => round($totales['acumulado'] * $porcentajeAnticipo, 2),
        ];

        $descuentos = (float) $planilla->descuentos;
        $multas = (float) $planilla->multas;

        $liquido = [
            'anterior' => round($totales['anterior'] - $amortizacion['anterior'] - $descuentos - $multas, 2),
            'actual' => round($totales['actual'] - $amortizacion['actual'] - $descuentos - $multas, 2),
            'acumulado' => round($totales['acumulado'] - $amortizacion['acumulado'] - $descuentos - $multas, 2),
        ];

        return [
            'frentes' => array_values($frentes),
            'totales' => $totales,
            'saldo' => round($totales['contratado'] - $totales['acumulado'], 2),
            'iva' => $iva,
            'total_mas_iva' => [
                'anterior' => round($totales['anterior'] + $iva['anterior'], 2),
                'actual' => round($totales['actual'] + $iva['actual'], 2),
                'acumulado' => round($totales['acumulado'] + $iva['acumulado'], 2),
            ],
            'anticipo' => $anticipo,
            'porcentaje_anticipo' => $porcentajeAnticipo,
            'amortizacion' => $amortizacion,
            'liquido' => $liquido,
        ];
    }

    /**
     * @return array{lineas: array<int, array{rubro: Rubro, calculo: array<string, float|string|null>}>, totales: array<string, float>}
     */
    public function consolidar(Contrato $contrato, ?Planilla $planilla): array
    {
        $contrato->loadMissing('catalogo.rubros');
        $grupos = [];

        if ($planilla) {
            $planilla->loadMissing('ejecuciones.rubro.frente');
            foreach ($planilla->ejecuciones as $ejecucion) {
                $rubro = $ejecucion->rubro;
                if (! $rubro || ! $rubro->frente || $rubro->frente->es_catalogo) {
                    continue;
                }
                $numero = $rubro->numero;
                if (! isset($grupos[$numero])) {
                    $grupos[$numero] = [
                        'rubro' => $rubro,
                        'frentes' => [],
                        'anterior' => 0.0,
                        'actual' => 0.0,
                    ];
                }
                $grupos[$numero]['frentes'][$rubro->frente->id] = $rubro->frente;
                $grupos[$numero]['anterior'] += (float) $ejecucion->cantidad_anterior;
                $grupos[$numero]['actual'] += (float) $ejecucion->cantidad_actual;
            }
        }

        if ($contrato->catalogo) {
            foreach ($contrato->catalogo->rubros as $rubro) {
                if (! isset($grupos[$rubro->numero])) {
                    $grupos[$rubro->numero] = [
                        'rubro' => $rubro,
                        'frentes' => [],
                        'anterior' => 0.0,
                        'actual' => 0.0,
                    ];
                } else {
                    $grupos[$rubro->numero]['rubro'] = $rubro;
                }
            }
        }

        ksort($grupos);
        $lineas = [];
        $totales = ['contratado' => 0.0, 'anterior' => 0.0, 'actual' => 0.0, 'acumulado' => 0.0];
        foreach ($grupos as $grupo) {
            $calculo = $this->linea($grupo['rubro'], $grupo['anterior'], $grupo['actual']);
            $lineas[] = [
                'rubro' => $grupo['rubro'],
                'frentes' => array_values($grupo['frentes'] ?? []),
                'calculo' => $calculo,
            ];
            $totales['contratado'] += $calculo['total_contratado'];
            $totales['anterior'] += $calculo['valor_anterior'];
            $totales['actual'] += $calculo['valor_actual'];
            $totales['acumulado'] += $calculo['valor_total'];
        }
        foreach ($totales as $clave => $valor) {
            $totales[$clave] = round($valor, 2);
        }

        return ['lineas' => $lineas, 'totales' => $totales];
    }

    public function sincronizarCantidadActual(PlanillaRubro $ejecucion): void
    {
        $total = $ejecucion->anexos()
            ->with('lineas')
            ->get()
            ->flatMap->lineas
            ->sum(fn ($linea) => (float) $linea->total);

        $ejecucion->update(['cantidad_actual' => round($total, 2)]);
    }
}
