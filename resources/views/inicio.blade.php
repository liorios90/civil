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
    @forelse ($contratos as $contrato)
        <div class="card fila">
            <div>
                <h2><a href="{{ route('contratos.show', $contrato) }}">{{ $contrato->codigo_proceso }}</a></h2>
                <p>{{ $contrato->objeto }}</p>
                <p>{{ $contrato->contratista }} · {{ $contrato->frentes_count }} frentes</p>
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
