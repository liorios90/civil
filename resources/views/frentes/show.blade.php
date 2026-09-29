@extends('layout')

@section('titulo', $frente->nombre)

@section('contenido')
    <p><a href="{{ route('contratos.show', $frente->contrato) }}">{{ $frente->contrato->codigo_proceso }}</a></p>
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <form method="post" action="{{ route('frentes.update', $frente) }}" class="fila card">
        @csrf
        @method('put')
        <input name="nombre" value="{{ $frente->nombre }}" required>
        <button type="submit">Guardar nombre</button>
        <button class="btn-rojo" type="submit" form="eliminar-frente">Eliminar frente</button>
    </form>
    <form id="eliminar-frente" method="post" action="{{ route('frentes.destroy', $frente) }}" onsubmit="return confirm('¿Eliminar este frente y sus cantidades?')">
        @csrf
        @method('delete')
    </form>
    @php
        $m = fn ($v) => number_format((float) $v, 2);
        $q = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    @endphp
    <form method="post" action="{{ route('rubros.guardar', $frente) }}">
        @csrf
        <div class="card">
            <h2>{{ $frente->nombre }}</h2>
            <div class="scroll ajustada">
                <table class="hoja vista">
                    <colgroup>
                        <col style="width:4%">
                        <col>
                        <col style="width:4%">
                        <col style="width:8%">
                        <col style="width:8%">
                        <col style="width:7%">
                        <col style="width:7%">
                        <col style="width:7%">
                        <col style="width:7%">
                        <col style="width:8%">
                        <col style="width:8%">
                        <col style="width:8%">
                        <col style="width:6%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th rowspan="2">No.</th>
                            <th rowspan="2">Descripción</th>
                            <th rowspan="2">U</th>
                            <th colspan="3">Contratado</th>
                            <th colspan="3">Cantidades ejecutadas</th>
                            <th colspan="3">Total en dólares</th>
                            <th rowspan="2">Hoja</th>
                        </tr>
                        <tr>
                            <th>Cantidad</th>
                            <th>Unitario</th>
                            <th>Total</th>
                            <th>Anterior</th>
                            <th>Actual</th>
                            <th>Total</th>
                            <th>Anterior</th>
                            <th>Actual</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lineas as $i => $linea)
                            @php
                                $rubro = $linea['rubro'];
                                $ejecucion = $linea['ejecucion'];
                                $k = $linea['calculo'];
                                $tieneHoja = $ejecucion && $ejecucion->anexos->contains(fn ($anexo) => $anexo->lineas->isNotEmpty());
                                $bloqueada = $tieneHoja;
                            @endphp
                            <tr>
                                <td class="num">{{ $rubro->numero }}<input type="hidden" name="filas[{{ $i }}][id]" value="{{ $rubro->id }}"></td>
                                <td>{{ $rubro->descripcion }}</td>
                                <td>{{ $rubro->unidad }}</td>
                                <td><input class="n" name="filas[{{ $i }}][cantidad_contratada]" value="{{ $k['cantidad_contratada'] + 0 }}"></td>
                                <td><input class="n" name="filas[{{ $i }}][precio_unitario]" value="{{ $k['precio_unitario'] + 0 }}"></td>
                                <td class="num">{{ $m($k['total_contratado']) }}</td>
                                <td><input class="n" name="filas[{{ $i }}][cantidad_anterior]" value="{{ $q($k['cantidad_anterior']) }}"></td>
                                <td>
                                    @if ($bloqueada)
                                        <input class="n" value="{{ $q($k['cantidad_actual']) }}" readonly>
                                    @else
                                        <input class="n" name="filas[{{ $i }}][cantidad_actual]" value="{{ $q($k['cantidad_actual']) }}">
                                    @endif
                                </td>
                                <td class="num">{{ $m($k['cantidad_total']) }}</td>
                                <td class="num">{{ $m($k['valor_anterior']) }}</td>
                                <td class="num">{{ $m($k['valor_actual']) }}</td>
                                <td class="num">{{ $m($k['valor_total']) }}</td>
                                <td>@if ($ejecucion)<a href="{{ route('anexos.show', $ejecucion) }}">Abrir</a>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="13">Este frente no tiene rubros. Defínelos en el contrato.</td></tr>
                        @endforelse
                        @if ($lineas !== [])
                            <tr class="cierre">
                                <td colspan="5">Trabajos realizados</td>
                                <td class="num">{{ $m($totales['contratado']) }}</td>
                                <td colspan="3"></td>
                                <td class="num">{{ $m($totales['anterior']) }}</td>
                                <td class="num">{{ $m($totales['actual']) }}</td>
                                <td class="num">{{ $m($totales['acumulado']) }}</td>
                                <td></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @if ($lineas !== [])
            <div class="barra"><button type="submit">Guardar</button></div>
        @endif
    </form>
@endsection
