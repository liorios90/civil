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
                <p>Al crear el contrato se definen los rubros. En el contrato se crean los frentes generales.</p>
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
            <div class="card"><small>Pendientes de aprobación</small><b>{{ count($tablero['pendientes']) }}</b></div>
        </div>
        @if ($tablero['pendientes'] !== [])
            <div class="card">
                <h2>Planillas pendientes de aprobación</h2>
                <div class="opciones">
                    @foreach ($tablero['pendientes'] as $pendiente)
                        <a class="opcion" href="{{ route('contratos.show', $pendiente['contrato']) }}">
                            <strong>{{ $pendiente['contrato']->codigo_proceso }}</strong>
                            <small>Planilla {{ $pendiente['planilla']->numero }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
    @forelse ($contratos as $contrato)
        <div class="card fila">
            <div>
                <h2><a href="{{ route('contratos.show', $contrato) }}">{{ $contrato->codigo_proceso }}</a></h2>
                <p>{{ $contrato->objeto }}</p>
                <p>{{ $contrato->contratista }} · {{ $contrato->frentes_count }} frentes</p>
                @php
                    $estadoPlanilla = $contrato->planillas->sortByDesc('id')->first()->estado ?? 'borrador';
                @endphp
                <p><span class="estado-planilla {{ $estadoPlanilla }}">{{ ['borrador' => 'En elaboración', 'pendiente' => 'Pendiente de aprobación', 'aprobada' => 'Aprobada'][$estadoPlanilla] ?? $estadoPlanilla }}</span></p>
            </div>
            @if (auth()->user()->esAdministrador())
                <div class="acciones">
                    <a class="btn" href="{{ route('contratos.edit', $contrato) }}">Editar</a>
                    <form method="post" action="{{ route('contratos.destroy', $contrato) }}" onsubmit="return confirm('¿Eliminar este contrato y sus frentes?')">
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
