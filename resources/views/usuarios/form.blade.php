@extends('layout')

@section('titulo', $usuario->exists ? $usuario->name : 'Nuevo usuario')

@section('contenido')
    <div class="card">
        <p><a class="btn secundario" href="{{ route('usuarios.index') }}">Volver a usuarios</a></p>
        <h1>{{ $usuario->exists ? 'Datos del usuario' : 'Nuevo usuario' }}</h1>
        @if ($errors->any())
            <div class="alerta">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <form method="post" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
            @csrf
            @if ($usuario->exists) @method('put') @endif
            <div class="grid">
                <label>Nombre<input name="name" value="{{ old('name', $usuario->name) }}" required></label>
                <label>Correo<input type="email" name="email" value="{{ old('email', $usuario->email) }}" required></label>
                <label>Contraseña<input type="password" name="password" @unless($usuario->exists) required @endunless autocomplete="new-password"></label>
                <label>Confirmar contraseña<input type="password" name="password_confirmation" @unless($usuario->exists) required @endunless autocomplete="new-password"></label>
            </div>
            @if ($usuario->exists)
                <p>Deje la contraseña en blanco si no desea cambiarla.</p>
            @endif
            <h2>Contratos</h2>
            <p>Marque los contratos a los que este usuario puede entrar.</p>
            @forelse ($contratos as $contrato)
                <label class="check">
                    <input type="checkbox" name="contratos[]" value="{{ $contrato->id }}" @checked(in_array($contrato->id, $seleccionados, false))>
                    {{ $contrato->codigo_proceso }}
                </label>
            @empty
                <p>Primero cree un contrato.</p>
            @endforelse
            <p><button type="submit">Guardar usuario</button></p>
        </form>
    </div>
@endsection
