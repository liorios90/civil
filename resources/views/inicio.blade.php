@extends('layout')

@section('titulo', 'Contratos')

@section('contenido')
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <div class="card fila">
        <div>
            <h1>Contratos</h1>
            <p>Al crear el contrato se definen los rubros. En el contrato se crean los frentes generales.</p>
        </div>
        <a class="btn" href="{{ route('contratos.create') }}">Nuevo contrato</a>
    </div>
    @forelse ($contratos as $contrato)
        <div class="card fila">
            <div>
                <h2><a href="{{ route('contratos.show', $contrato) }}">{{ $contrato->codigo_proceso }}</a></h2>
                <p>{{ $contrato->objeto }}</p>
                <p>{{ $contrato->contratista }} · {{ $contrato->frentes_count }} frentes</p>
            </div>
            <div class="acciones">
                <a class="btn" href="{{ route('contratos.edit', $contrato) }}">Editar</a>
                <form method="post" action="{{ route('contratos.destroy', $contrato) }}" onsubmit="return confirm('¿Eliminar este contrato y sus frentes?')">
                    @csrf
                    @method('delete')
                    <button class="btn-rojo" type="submit">Eliminar</button>
                </form>
            </div>
        </div>
    @empty
        <div class="card"><p>Todavía no hay contratos.</p></div>
    @endforelse
@endsection
