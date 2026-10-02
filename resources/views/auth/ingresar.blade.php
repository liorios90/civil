@extends('layout')

@section('titulo', 'Ingresar')

@section('contenido')
    <div class="card" style="max-width: 460px">
        <h1>Ingresar</h1>
        @if ($errors->any())
            <div class="alerta">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <form method="post" action="{{ route('ingresar.enviar') }}">
            @csrf
            <div class="grid">
                <label class="ancho">Correo<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
                <label class="ancho">Contraseña<input type="password" name="password" required></label>
                <label class="check"><input type="checkbox" name="recordar" value="1"> Recordarme</label>
            </div>
            <p><button type="submit">Ingresar</button></p>
        </form>
    </div>
@endsection
