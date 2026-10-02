@extends('fiscalizacion.layout')

@php
    $rubro = $ejecucion->rubro;
    $imagenes = $anexo?->imagenes ?? collect();
    $iniciales = $imagenes->reject(fn ($imagen) => str_starts_with($imagen->ruta, 'anexos/otras/'));
    $otras = $imagenes->filter(fn ($imagen) => str_starts_with($imagen->ruta, 'anexos/otras/'));
    $q = fn ($v) => $v === null || $v === '' ? '' : number_format((float) $v, 2, '.', '');
@endphp

@section('titulo', 'Rubro '.$rubro->numero)
@section('entidad', $contrato->entidad)

@section('contenido')
    <p><a href="{{ route('fiscalizacion.show', $token) }}">Volver a la planilla</a></p>
    <div class="card">
        <h1>{{ $rubro->numero }}. {{ $rubro->descripcion }}</h1>
        <p class="muted">{{ $rubro->frente->nombre }} · Unidad {{ $rubro->unidad }}</p>
        <div class="cifras">
            <div><small>Anterior</small><b>{{ $q($ejecucion->cantidad_anterior) }}</b></div>
            <div><small>Actual</small><b>{{ $q($ejecucion->cantidad_actual) }}</b></div>
            <div><small>Acumulado</small><b>{{ $q($calculo['cantidad_total']) }}</b></div>
        </div>
    </div>

    <div class="card">
        <h2>Imágenes</h2>
        @if ($iniciales->isEmpty())
            <p class="muted">No hay imágenes en esta sección.</p>
        @else
            <div class="galeria">
                @foreach ($iniciales as $imagen)
                    <img src="{{ url('fiscalizacion/'.$token.'/archivos/'.$imagen->ruta) }}" alt="Imagen de la medición">
                @endforeach
            </div>
        @endif
    </div>

    <div class="card" style="overflow-x:auto">
        <h2>Mediciones</h2>
        @if (! $anexo || $anexo->lineas->isEmpty())
            <p class="muted">No hay mediciones cargadas.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Base 1</th>
                        <th>Base 2</th>
                        <th>Altura</th>
                        <th>Número</th>
                        <th>Longitud</th>
                        <th>Área</th>
                        <th>Volumen</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($anexo->lineas as $linea)
                        <tr>
                            <td>{{ $linea->descripcion }}</td>
                            <td class="n">{{ $q($linea->base1) }}</td>
                            <td class="n">{{ $q($linea->base2) }}</td>
                            <td class="n">{{ $q($linea->altura) }}</td>
                            <td class="n">{{ $q($linea->numero) }}</td>
                            <td class="n">{{ $q($linea->longitud) }}</td>
                            <td class="n">{{ $q($linea->area) }}</td>
                            <td class="n">{{ $q($linea->volumen) }}</td>
                            <td class="n">{{ $q($linea->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <h2>Otras imágenes</h2>
        @if ($otras->isEmpty())
            <p class="muted">No hay otras imágenes.</p>
        @else
            <div class="galeria">
                @foreach ($otras as $imagen)
                    <img src="{{ url('fiscalizacion/'.$token.'/archivos/'.$imagen->ruta) }}" alt="Otra imagen">
                @endforeach
            </div>
        @endif
    </div>
@endsection
