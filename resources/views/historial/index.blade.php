@extends('layout')

@section('titulo', 'Historial de cambios')

@section('contenido')
    <p>
        @if ($ejecucion)
            <a href="{{ route('anexos.show', $ejecucion) }}">Volver a la hoja del rubro</a>
        @elseif ($frente && ! $frente->es_catalogo)
            <a href="{{ route('frentes.show', $frente) }}">Volver a la planilla</a>
        @elseif ($frente && auth()->user()->esAdministrador())
            <a href="{{ route('contratos.edit', $contrato) }}">Volver a los rubros del contrato</a>
        @else
            <a href="{{ route('contratos.show', $contrato) }}">Volver al contrato</a>
        @endif
    </p>
    <div class="card">
        <h1>Historial de cambios</h1>
        <p>
            {{ $contrato->codigo_proceso }}
            @if ($ejecucion)
                · Rubro {{ $ejecucion->rubro->numero }}: {{ $ejecucion->rubro->descripcion }}
            @elseif ($frente)
                · {{ $frente->es_catalogo ? 'Catálogo de rubros' : $frente->nombre }}
            @endif
        </p>
        <p>Fechas y horas de Ecuador.</p>
    </div>
    @include('historial.tabla', ['cambios' => $cambios])
@endsection
