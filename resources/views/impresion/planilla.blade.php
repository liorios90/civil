@extends('impresion.layout')

@section('titulo', 'Planilla '.$planilla->numero)

@section('contenido')
    @php
        $m = fn ($v) => $v === null || $v === '' ? '' : number_format((float) $v, 2);
        $q = fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $c = $planilla->contrato;
    @endphp
    <div class="cabecera centro">
        <h1>MUNICIPIO DEL DISTRITO METROPOLITANO DE QUITO</h1>
        <p>ADMINISTRACIÓN ZONAL NORTE "EUGENIO ESPEJO"</p>
        <p>UNIDAD DE FISCALIZACIÓN</p>
        <h2>PLANILLA DE OBRAS EJECUTADAS</h2>
    </div>
    <table style="margin-bottom:8px">
        <tr>
            <td colspan="8"><b>Objeto:</b> {{ $c->objeto }}</td>
            <td colspan="4"><b>Planilla N°</b> {{ $planilla->numero }}</td>
        </tr>
        <tr>
            <td colspan="8"><b>Código:</b> {{ $c->codigo_proceso }}</td>
            <td colspan="4"><b>Período:</b> {{ optional($planilla->periodo_desde)->format('d/m/Y') }} al {{ optional($planilla->periodo_hasta)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td colspan="8"><b>Contratista:</b> {{ $c->contratista }}</td>
            <td colspan="4"><b>Monto contratado:</b> {{ $m($liquidacion['totales']['contratado']) }}</td>
        </tr>
        <tr>
            <td colspan="8"><b>Fiscalizador:</b> {{ $c->fiscalizador }}</td>
            <td colspan="4"><b>Monto de planilla:</b> {{ $m($liquidacion['totales']['actual']) }}</td>
        </tr>
        <tr>
            <td colspan="8"><b>Administrador:</b> {{ $c->administrador }}</td>
            <td colspan="4"><b>Ejecutado acumulado:</b> {{ $m($liquidacion['totales']['acumulado']) }}</td>
        </tr>
    </table>
    <table>
        <thead>
            <tr>
                <th rowspan="2">No.</th>
                <th rowspan="2">Descripción del rubro</th>
                <th rowspan="2">U</th>
                <th colspan="3">Contratado</th>
                <th colspan="3">Cantidades ejecutadas</th>
                <th colspan="3">Total en dólares</th>
                <th colspan="2">Incrementos</th>
                <th colspan="2">Decrementos</th>
                <th>%</th>
                <th rowspan="2">Observaciones</th>
            </tr>
            <tr>
                <th>Cant.</th><th>Unit.</th><th>Total</th>
                <th>Ant.</th><th>Actual</th><th>Total</th>
                <th>Ant.</th><th>Actual</th><th>Total</th>
                <th>Cant.</th><th>P. total</th>
                <th>Cant.</th><th>P. total</th>
                <th>%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($liquidacion['frentes'] as $grupo)
                <tr class="frente"><td colspan="18">{{ $grupo['frente']->nombre }}</td></tr>
                @foreach ($grupo['lineas'] as $linea)
                    @php($k = $linea['calculo'])
                    <tr>
                        <td>{{ $linea['rubro']->numero }}</td>
                        <td>{{ $linea['rubro']->descripcion }}</td>
                        <td>{{ $linea['rubro']->unidad }}</td>
                        <td class="n">{{ $q($k['cantidad_contratada']) }}</td>
                        <td class="n">{{ $m($k['precio_unitario']) }}</td>
                        <td class="n">{{ $m($k['total_contratado']) }}</td>
                        <td class="n">{{ $q($k['cantidad_anterior']) }}</td>
                        <td class="n">{{ $q($k['cantidad_actual']) }}</td>
                        <td class="n">{{ $q($k['cantidad_total']) }}</td>
                        <td class="n">{{ $m($k['valor_anterior']) }}</td>
                        <td class="n">{{ $m($k['valor_actual']) }}</td>
                        <td class="n">{{ $m($k['valor_total']) }}</td>
                        <td class="n">{{ $q($k['incremento_cantidad']) }}</td>
                        <td class="n">{{ $m($k['incremento_valor']) }}</td>
                        <td class="n">{{ $q($k['decremento_cantidad']) }}</td>
                        <td class="n">{{ $m($k['decremento_valor']) }}</td>
                        <td class="n">{{ $k['porcentaje'] === null ? '' : number_format($k['porcentaje'] * 100, 2) }}</td>
                        <td>{{ $k['observacion'] }}</td>
                    </tr>
                @endforeach
                <tr class="sub">
                    <td colspan="5">SUBTOTAL</td>
                    <td class="n">{{ $m($grupo['subtotal']['contratado']) }}</td>
                    <td colspan="3"></td>
                    <td class="n">{{ $m($grupo['subtotal']['anterior']) }}</td>
                    <td class="n">{{ $m($grupo['subtotal']['actual']) }}</td>
                    <td class="n">{{ $m($grupo['subtotal']['acumulado']) }}</td>
                    <td colspan="6"></td>
                </tr>
            @endforeach
            <tr class="cierre">
                <td colspan="5">TRABAJOS REALIZADOS</td>
                <td class="n">{{ $m($liquidacion['totales']['contratado']) }}</td>
                <td colspan="3"></td>
                <td class="n">{{ $m($liquidacion['totales']['anterior']) }}</td>
                <td class="n">{{ $m($liquidacion['totales']['actual']) }}</td>
                <td class="n">{{ $m($liquidacion['totales']['acumulado']) }}</td>
                <td colspan="6">Saldo {{ $m($liquidacion['saldo']) }}</td>
            </tr>
            <tr class="cierre">
                <td colspan="9">IVA {{ $planilla->iva_porcentaje }} %</td>
                <td class="n">{{ $m($liquidacion['iva']['anterior']) }}</td>
                <td class="n">{{ $m($liquidacion['iva']['actual']) }}</td>
                <td class="n">{{ $m($liquidacion['iva']['acumulado']) }}</td>
                <td colspan="6"></td>
            </tr>
            <tr class="cierre">
                <td colspan="9">AMORTIZACIÓN ANTICIPO {{ number_format($liquidacion['porcentaje_anticipo'] * 100, 0) }} %</td>
                <td class="n">{{ $m($liquidacion['amortizacion']['anterior']) }}</td>
                <td class="n">{{ $m($liquidacion['amortizacion']['actual']) }}</td>
                <td class="n">{{ $m($liquidacion['amortizacion']['acumulado']) }}</td>
                <td colspan="6"></td>
            </tr>
            <tr class="cierre">
                <td colspan="9">LÍQUIDO A PAGAR</td>
                <td class="n">{{ $m($liquidacion['liquido']['anterior']) }}</td>
                <td class="n">{{ $m($liquidacion['liquido']['actual']) }}</td>
                <td class="n">{{ $m($liquidacion['liquido']['acumulado']) }}</td>
                <td colspan="6"></td>
            </tr>
        </tbody>
    </table>
    <div class="firmas">
        <div>{{ $c->administrador }}<br>ADMINISTRADOR DE CONTRATO</div>
        <div>{{ $c->fiscalizador }}<br>FISCALIZADOR</div>
        <div>{{ $c->contratista }}<br>CONTRATISTA</div>
    </div>
@endsection
