@extends('layout')

@section('titulo', 'Historial de cambios')

@section('contenido')
    <div class="card">
        <h1>Historial de cambios</h1>
        <p>Quién creó, cambió o borró planillas, rubros, cantidades, mediciones e imágenes en los contratos de la empresa. Fechas y horas de Ecuador.</p>
        <form method="get" action="{{ route('historial.general') }}" class="filtros">
            <label>Usuario
                <select name="usuario">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}" @selected((string) request('usuario') === (string) $usuario->id)>{{ $usuario->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>Acción
                <select name="accion">
                    <option value="">Todas</option>
                    <option value="creado" @selected(request('accion') === 'creado')>Creó</option>
                    <option value="modificado" @selected(request('accion') === 'modificado')>Modificó</option>
                    <option value="eliminado" @selected(request('accion') === 'eliminado')>Eliminó</option>
                </select>
            </label>
            <label>Contrato
                <select name="contrato">
                    <option value="">Todos</option>
                    @foreach ($contratos as $contrato)
                        <option value="{{ $contrato->id }}" @selected((string) request('contrato') === (string) $contrato->id)>{{ $contrato->codigo_proceso }}</option>
                    @endforeach
                </select>
            </label>
            <label>Desde<input type="date" name="desde" value="{{ request('desde') }}"></label>
            <label>Hasta<input type="date" name="hasta" value="{{ request('hasta') }}"></label>
            <div class="acciones">
                <button type="submit">Filtrar</button>
                <a class="btn secundario" href="{{ route('historial.general') }}">Limpiar</a>
            </div>
        </form>
        <p>{{ $cambios->total() }} {{ $cambios->total() === 1 ? 'cambio' : 'cambios' }}.</p>
    </div>
    @include('historial.tabla', ['cambios' => $cambios, 'conContrato' => true])
@endsection
