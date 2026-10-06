@extends('layout')

@section('titulo', 'Contratos')

@section('contenido')
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <div class="card fila">
        <div>
            <h1>Contratos</h1>
            @if (auth()->user()->esAdministrador())
                <p>Al crear el contrato se definen los rubros. En el contrato se crean las planillas.</p>
            @else
                <p>Estos son los contratos que le asignó el administrador.</p>
            @endif
        </div>
        @if (auth()->user()->esAdministrador())
            <a class="btn" href="{{ route('contratos.create') }}">Nuevo contrato</a>
        @endif
    </div>
    @if ($tablero)
        @php
            $dinero = fn ($v) => number_format((float) $v, 2);
        @endphp
        <div class="tablero">
            <div class="card"><small>Obras activas</small><b>{{ $tablero['activas'] }}</b></div>
            <div class="card"><small>Avance</small><b>{{ $tablero['avance'] === null ? '—' : number_format($tablero['avance'], 2).' %' }}</b></div>
            <div class="card"><small>Monto ejecutado</small><b>{{ $dinero($tablero['ejecutado']) }}</b><small>de {{ $dinero($tablero['contratado']) }} contratado</small></div>
        </div>
    @endif
    @forelse ($contratos as $contrato)
        <div class="card fila">
            <div>
                <h2><a href="{{ route('contratos.show', $contrato) }}">{{ $contrato->codigo_proceso }}</a></h2>
                <p>{{ $contrato->objeto }}</p>
                <p>{{ $contrato->contratista }} · {{ $contrato->frentes_count }} {{ $contrato->frentes_count === 1 ? 'planilla' : 'planillas' }}</p>
            </div>
            @if (auth()->user()->esAdministrador())
                <div class="acciones">
                    <a class="btn" href="{{ route('contratos.edit', $contrato) }}">Editar</a>
                    <form method="post" action="{{ route('contratos.destroy', $contrato) }}" onsubmit="return confirm('¿Eliminar este contrato y sus planillas?')">
                        @csrf
                        @method('delete')
                        <button class="btn-rojo" type="submit">Eliminar</button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <div class="card"><p>{{ auth()->user()->esAdministrador() ? 'Todavía no hay contratos.' : 'No tiene contratos asignados.' }}</p></div>
    @endforelse
@endsection
