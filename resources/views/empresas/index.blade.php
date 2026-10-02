@extends('layout')

@section('titulo', 'Empresas')

@section('contenido')
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <div class="card fila">
        <div>
            <h1>Empresas</h1>
            <p>Cada empresa tiene un usuario administrador. Ese administrador trabaja los contratos y las planillas.</p>
        </div>
        <a class="btn" href="{{ route('empresas.create') }}">Nueva empresa</a>
    </div>
    @forelse ($empresas as $empresa)
        <div class="card fila">
            <div>
                <h2>{{ $empresa->nombre }}</h2>
                <p>{{ $empresa->responsable }} · desde {{ $empresa->fecha_inicio->format('d/m/Y') }}</p>
                <p>{{ $empresa->activo ? 'Activa' : 'Inactiva' }}</p>
                @if ($empresa->telefonos)
                    <p>{{ $empresa->telefonos }}</p>
                @endif
                <p>Administrador: {{ $empresa->administrador->name ?? 'Sin usuario' }}@if ($empresa->administrador) · {{ $empresa->administrador->email }}@endif</p>
            </div>
            <a class="btn" href="{{ route('empresas.edit', $empresa) }}">Editar</a>
        </div>
    @empty
        <div class="card"><p>Todavía no hay empresas.</p></div>
    @endforelse
@endsection
