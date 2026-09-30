@extends('layout')

@section('titulo', 'Comparación de avance')

@section('contenido')
    @php
        $m = fn ($v) => number_format((float) $v, 2);
        $q = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $pct = function ($plan, $real) {
            $plan = (float) $plan;
            if ($plan <= 0) {
                return null;
            }

            return round(((float) $real / $plan) * 100, 2);
        };
        $ancho = fn ($valor) => min(100, max(0, (float) $valor));
        $contrato = $planilla->contrato;
    @endphp
    <style>
        .informe { max-width: 1100px; }
        .resumen { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
        .medida { background: #fff; border: 1px solid #d9e0e7; border-radius: 8px; padding: 14px 16px; }
        .medida span { display: block; color: #4a5b6d; font-size: 12px; letter-spacing: .04em; text-transform: uppercase; }
        .medida strong { display: block; margin-top: 6px; font-size: 22px; font-variant-numeric: tabular-nums; }
        .medida small { color: #4a5b6d; }
        .pista { height: 14px; background: #e8eef5; border-radius: 999px; overflow: hidden; }
        .pista i { display: block; height: 100%; background: #0f3d68; border-radius: 999px; }
        .pista.mini { height: 8px; margin-top: 6px; }
        .pista.supera i { background: #8a5a00; }
        .estado { display: inline-block; border-radius: 999px; padding: 2px 8px; font-size: 12px; background: #e8eef5; }
        .estado.ok { background: #e5f4e4; color: #1d6b32; }
        .estado.curso { background: #e7f0fa; color: #0f3d68; }
        .estado.sobre { background: #fff4e5; color: #8a5a00; }
        @media (max-width: 800px) { .resumen { grid-template-columns: 1fr 1fr; } }
        @media print {
            header, .no-imprimir { display: none; }
            body, main { background: #fff; padding: 0; }
            .card, .medida { break-inside: avoid; }
        }
    </style>
    <div class="informe">
        <div class="card fila">
            <div>
                <p style="margin:0;color:#4a5b6d;font-size:12px;letter-spacing:.04em;text-transform:uppercase">Comparación de avance</p>
                <h1>{{ $contrato->codigo_proceso }}</h1>
                <p>{{ $contrato->objeto }}</p>
                <p>{{ $contrato->contratista }} · Planilla {{ $planilla->numero }}</p>
            </div>
            <div class="acciones no-imprimir">
                <a class="btn" href="{{ route('contratos.show', $contrato) }}">Volver</a>
                <button type="button" onclick="window.print()">Imprimir</button>
            </div>
        </div>

        <div class="resumen">
            <div class="medida">
                <span>Planificado</span>
                <strong>{{ $m($planificado) }}</strong>
                <small>Monto contratado</small>
            </div>
            <div class="medida">
                <span>Real</span>
                <strong>{{ $m($real) }}</strong>
                <small>Ejecutado acumulado</small>
            </div>
            <div class="medida">
                <span>Brecha</span>
                <strong>{{ $m($brecha) }}</strong>
                <small>{{ $brecha < 0 ? 'Ejecutado por encima del contrato' : 'Aún por ejecutar' }}</small>
            </div>
            <div class="medida">
                <span>Avance real</span>
                <strong>{{ $porcentaje === null ? '—' : $m($porcentaje).' %' }}</strong>
                <small>Sobre lo planificado</small>
            </div>
        </div>

        <div class="card">
            <h2>Avance del contrato</h2>
            @if ($porcentaje === null)
                <p>Todavía no hay un monto contratado para comparar.</p>
            @else
                <div class="pista {{ $porcentaje > 100 ? 'supera' : '' }}"><i style="width: {{ $ancho($porcentaje) }}%"></i></div>
                <p>Lo ejecutado cubre el {{ $m($porcentaje) }} % del monto planificado en el contrato.</p>
            @endif
        </div>

        <div class="card">
            <h2>Detalle por rubro</h2>
            <div class="scroll ajustada">
                <table class="hoja vista">
                    <thead>
                        <tr>
                            <th rowspan="2">No.</th>
                            <th rowspan="2">Descripción</th>
                            <th rowspan="2">U</th>
                            <th colspan="2">Cantidad</th>
                            <th colspan="3">Monto</th>
                            <th rowspan="2">Avance</th>
                        </tr>
                        <tr>
                            <th>Planificada</th>
                            <th>Real</th>
                            <th>Planificado</th>
                            <th>Real</th>
                            <th>Brecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($liquidacion['frentes'] as $grupo)
                            @php
                                $frentePct = $pct($grupo['subtotal']['contratado'], $grupo['subtotal']['acumulado']);
                            @endphp
                            <tr class="frente">
                                <td colspan="8">{{ $grupo['frente']->nombre }}</td>
                                <td>{{ $frentePct === null ? '—' : $m($frentePct).' %' }}</td>
                            </tr>
                            @foreach ($grupo['lineas'] as $linea)
                                @php
                                    $k = $linea['calculo'];
                                    $avance = $pct($k['total_contratado'], $k['valor_total']);
                                    $clase = $avance === null ? '' : ($avance > 100 ? 'sobre' : ($avance >= 100 ? 'ok' : ($avance > 0 ? 'curso' : '')));
                                    $texto = $avance === null ? 'Sin monto' : ($avance > 100 ? 'Supera' : ($avance >= 100 ? 'Cumplido' : ($avance > 0 ? 'En curso' : 'Sin avance')));
                                @endphp
                                <tr>
                                    <td class="num">{{ $linea['rubro']->numero }}</td>
                                    <td>
                                        {{ $linea['rubro']->descripcion }}
                                        @if ($avance !== null)
                                            <div class="pista mini {{ $avance > 100 ? 'supera' : '' }}"><i style="width: {{ $ancho($avance) }}%"></i></div>
                                        @endif
                                    </td>
                                    <td>{{ $linea['rubro']->unidad }}</td>
                                    <td class="num">{{ $q($k['cantidad_contratada']) }}</td>
                                    <td class="num">{{ $q($k['cantidad_total']) }}</td>
                                    <td class="num">{{ $m($k['total_contratado']) }}</td>
                                    <td class="num">{{ $m($k['valor_total']) }}</td>
                                    <td class="num">{{ $m($k['total_contratado'] - $k['valor_total']) }}</td>
                                    <td><span class="estado {{ $clase }}">{{ $texto }}{{ $avance === null ? '' : ' '.$m($avance).' %' }}</span></td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="9">Esta planilla todavía no tiene cantidades ejecutadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
