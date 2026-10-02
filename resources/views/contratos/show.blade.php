@extends('layout')

@section('titulo', $contrato->codigo_proceso)

@section('contenido')
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <div class="card fila">
        <div>
            <h1>{{ $contrato->entidad }}</h1>
            <p>{{ $contrato->objeto }}</p>
        </div>
        <div class="acciones">
            @if (auth()->user()->esAdministrador())
                <a class="btn" href="{{ route('contratos.edit', $contrato) }}">Rubros</a>
            @endif
            <a class="btn" href="{{ route('inicio') }}">Contratos</a>
            @if ($planilla)
                <a class="btn" href="{{ route('impresion.planilla', $planilla) }}" target="_blank">Imprimir planilla</a>
                <a class="btn" href="{{ route('impresion.excel', $planilla) }}">Exportar Excel</a>
                <a class="btn" href="{{ route('avance.comparacion', $planilla) }}">Comparar avance</a>
            @endif
        </div>
    </div>
    @if (auth()->user()->esAdministrador())
        <div class="card">
            <h2>Nueva plantilla</h2>
            <form method="post" action="{{ route('frentes.store', $contrato) }}" class="fila">
                @csrf
                <input name="nombre" value="{{ old('nombre', 'plantilla '.($contrato->frentes->count() + 1)) }}" required>
                <button type="submit">Crear plantilla</button>
            </form>
            @error('nombre')
                <p>{{ $message }}</p>
            @enderror
        </div>
    @endif
    <div class="card">
        <h2>Frentes generales</h2>
        <p>Cada plantilla guarda sus rubros, cantidades y hojas de medición.</p>
        <div class="opciones">
            @forelse ($contrato->frentes as $frente)
                <a class="opcion" href="{{ route('frentes.show', $frente) }}">
                    <strong>{{ $frente->nombre }}</strong>
                    <small>{{ $frente->rubros->count() }} rubros</small>
                </a>
            @empty
                <p>Todavía no hay frentes. Crea el primero arriba.</p>
            @endforelse
        </div>
    </div>
@endsection
