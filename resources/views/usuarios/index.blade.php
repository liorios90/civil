@extends('layout')

@section('titulo', 'Usuarios')

@section('contenido')
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <div class="card fila">
        <div>
            <h1>Usuarios</h1>
            <p>Cada usuario entra solo a los contratos que le marque.</p>
        </div>
        <a class="btn" href="{{ route('usuarios.create') }}">Nuevo usuario</a>
    </div>
    @forelse ($usuarios as $usuario)
        <div class="card fila">
            <div>
                <h2>{{ $usuario->name }}</h2>
                <p>{{ $usuario->email }}</p>
                <p>{{ $usuario->contratos->pluck('codigo_proceso')->join(', ') ?: 'Sin contratos' }}</p>
            </div>
            <div class="acciones">
                <a class="btn" href="{{ route('usuarios.edit', $usuario) }}">Editar</a>
                <form method="post" action="{{ route('usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Eliminar este usuario?')">
                    @csrf
                    @method('delete')
                    <button class="btn-rojo" type="submit">Eliminar</button>
                </form>
            </div>
        </div>
    @empty
        <div class="card"><p>Todavía no hay usuarios.</p></div>
    @endforelse
@endsection
