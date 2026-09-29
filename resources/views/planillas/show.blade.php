@extends('layout')

@section('titulo', 'Planilla '.$planilla->numero)

@section('contenido')
    @php
        $money = fn ($v) => $v === null ? '' : number_format((float) $v, 2);
        $qty = fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    @endphp
    <div class="card">
        <h1>Planilla {{ $planilla->numero }}</h1>
        <p>{{ $planilla->contrato->objeto }}</p>
        <p>
            {{ $planilla->contrato->codigo_proceso }} · {{ $planilla->contrato->contratista }}
            · {{ optional($planilla->periodo_desde)->format('d/m/Y') }} al {{ optional($planilla->periodo_hasta)->format('d/m/Y') }}
        </p>
        <p><a href="{{ route('contratos.show', $planilla->contrato) }}">Datos del contrato</a></p>
    </div>
    <div class="alerta">
        La amortización de cada período es el valor de los trabajos de esa columna por el {{ number_format($liquidacion['porcentaje_anticipo'] * 100, 0) }} % de anticipo.
        En el Excel, la columna del período actual amortiza el anticipo completo del contrato y el líquido sale negativo.
    </div>
    <div class="scroll">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Descripción</th>
                    <th>U</th>
                    <th>Cant.</th>
                    <th>P. unit.</th>
                    <th>Total</th>
                    <th>Ant.</th>
                    <th>Actual</th>
                    <th>Acum.</th>
                    <th>Valor ant.</th>
                    <th>Valor actual</th>
                    <th>Valor acum.</th>
                    <th>%</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($liquidacion['frentes'] as $grupo)
                    <tr class="frente"><td colspan="14">{{ $grupo['frente']->nombre }}</td></tr>
                    @foreach ($grupo['lineas'] as $linea)
                        @php($c = $linea['calculo'])
                        <tr>
                            <td>{{ $linea['rubro']->numero }}</td>
                            <td>{{ $linea['rubro']->descripcion }}</td>
                            <td>{{ $linea['rubro']->unidad }}</td>
                            <td class="num">{{ $qty($c['cantidad_contratada']) }}</td>
                            <td class="num">{{ $money($c['precio_unitario']) }}</td>
                            <td class="num">{{ $money($c['total_contratado']) }}</td>
                            <td class="num">{{ $qty($c['cantidad_anterior']) }}</td>
                            <td class="num">{{ $qty($c['cantidad_actual']) }}</td>
                            <td class="num">{{ $qty($c['cantidad_total']) }}</td>
                            <td class="num">{{ $money($c['valor_anterior']) }}</td>
                            <td class="num">{{ $money($c['valor_actual']) }}</td>
                            <td class="num">{{ $money($c['valor_total']) }}</td>
                            <td class="num">{{ $c['porcentaje'] === null ? '' : number_format($c['porcentaje'] * 100, 2).'%' }}</td>
                            <td><a href="{{ route('anexos.show', $linea['ejecucion']) }}">Anexo</a></td>
                        </tr>
                    @endforeach
                    <tr class="subtotal">
                        <td colspan="5">Subtotal</td>
                        <td class="num">{{ $money($grupo['subtotal']['contratado']) }}</td>
                        <td colspan="3"></td>
                        <td class="num">{{ $money($grupo['subtotal']['anterior']) }}</td>
                        <td class="num">{{ $money($grupo['subtotal']['actual']) }}</td>
                        <td class="num">{{ $money($grupo['subtotal']['acumulado']) }}</td>
                        <td colspan="2"></td>
                    </tr>
                @endforeach
                <tr class="cierre">
                    <td colspan="5">Trabajos realizados</td>
                    <td class="num">{{ $money($liquidacion['totales']['contratado']) }}</td>
                    <td colspan="3"></td>
                    <td class="num">{{ $money($liquidacion['totales']['anterior']) }}</td>
                    <td class="num">{{ $money($liquidacion['totales']['actual']) }}</td>
                    <td class="num">{{ $money($liquidacion['totales']['acumulado']) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="cierre">
                    <td colspan="9">IVA {{ $planilla->iva_porcentaje }} %</td>
                    <td class="num">{{ $money($liquidacion['iva']['anterior']) }}</td>
                    <td class="num">{{ $money($liquidacion['iva']['actual']) }}</td>
                    <td class="num">{{ $money($liquidacion['iva']['acumulado']) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="cierre">
                    <td colspan="9">Amortización anticipo</td>
                    <td class="num">{{ $money($liquidacion['amortizacion']['anterior']) }}</td>
                    <td class="num">{{ $money($liquidacion['amortizacion']['actual']) }}</td>
                    <td class="num">{{ $money($liquidacion['amortizacion']['acumulado']) }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="cierre">
                    <td colspan="9">Líquido (sin IVA, descontada la amortización)</td>
                    <td class="num">{{ $money($liquidacion['liquido']['anterior']) }}</td>
                    <td class="num">{{ $money($liquidacion['liquido']['actual']) }}</td>
                    <td class="num">{{ $money($liquidacion['liquido']['acumulado']) }}</td>
                    <td colspan="2">Saldo contractual $ {{ $money($liquidacion['saldo']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
