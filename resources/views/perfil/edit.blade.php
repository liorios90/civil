@extends('layout')

@section('titulo', 'Perfil')

@section('contenido')
    <div class="card" style="max-width:480px">
        <p><a class="btn secundario" href="{{ route('inicio') }}">Volver al menú principal</a></p>
        <h1>Perfil</h1>
        <p style="color:#4a5b6d;margin-top:0">{{ $usuario->name }} · {{ $usuario->email }}</p>
        <h2>Cambiar clave</h2>
        <p style="color:#4a5b6d;margin-top:0">Escriba su clave actual y la nueva (mínimo 8 caracteres).</p>
        @if (session('estado'))
            <div class="alerta">{{ session('estado') }}</div>
        @endif
        @if ($errors->any())
            <div class="alerta">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <form method="post" action="{{ route('perfil.update') }}">
            @csrf
            @method('put')
            <div class="grid" style="grid-template-columns:1fr">
                <label>Clave actual<input type="password" name="password_actual" required autocomplete="current-password"></label>
                <label>Clave nueva<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                <label>Confirmar clave nueva<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
            </div>
            <p style="margin-top:16px"><button type="submit">Guardar clave</button></p>
        </form>
    </div>
@endsection
