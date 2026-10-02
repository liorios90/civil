@extends('fiscalizacion.layout')

@section('titulo', $contrato->codigo_proceso)
@section('entidad', $contrato->entidad)

@section('contenido')
    @php
        $m = fn ($v) => number_format((float) $v, 2);
        $q = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $totales = $liquidacion['totales'] ?? null;
        $porcentaje = $totales && $totales['contratado'] > 0
            ? round($totales['acumulado'] / $totales['contratado'] * 100, 2)
            : null;
    @endphp
    <div class="card">
        <h1>{{ $contrato->codigo_proceso }}</h1>
        <p>{{ $contrato->objeto }}</p>
        <p class="muted">Contratista: {{ $contrato->contratista }}@if ($contrato->fiscalizador) · Fiscalizador: {{ $contrato->fiscalizador }}@endif</p>
        @if ($totales)
            <div class="cifras">
                <div><small>Contratado</small><b>{{ $m($totales['contratado']) }}</b></div>
                <div><small>Esta planilla</small><b>{{ $m($totales['actual']) }}</b></div>
                <div><small>Ejecutado{{ $porcentaje !== null ? ' · '.$m($porcentaje).' %' : '' }}</small><b>{{ $m($totales['acumulado']) }}</b></div>
            </div>
        @endif
    </div>
    @forelse ($liquidacion['frentes'] ?? [] as $grupo)
        <h2>{{ $grupo['frente']->nombre }}</h2>
        @foreach ($grupo['lineas'] as $linea)
            @php($k = $linea['calculo'])
            <a class="card rubro" href="{{ route('fiscalizacion.hoja', [$token, $linea['ejecucion']->id]) }}">
                <strong>{{ $linea['rubro']->numero }}. {{ $linea['rubro']->descripcion }}</strong>
                <p class="muted">{{ $linea['rubro']->unidad }} · Contratado {{ $q($k['cantidad_contratada']) }} · Anterior {{ $q($k['cantidad_anterior']) }} · Actual {{ $q($k['cantidad_actual']) }} · {{ $m($k['valor_actual']) }}</p>
                <small>Ver hoja de medición y fotos</small>
            </a>
        @endforeach
    @empty
        <div class="card"><p>Todavía no hay rubros en esta planilla.</p></div>
    @endforelse
@endsection
