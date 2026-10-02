@extends('layout')

@section('titulo', $empresa->exists ? $empresa->nombre : 'Nueva empresa')

@section('contenido')
    <div class="card">
        <p><a class="btn secundario" href="{{ route('empresas.index') }}">Volver a empresas</a></p>
        <h1>{{ $empresa->exists ? 'Datos de la empresa' : 'Nueva empresa' }}</h1>
        @if ($errors->any())
            <div class="alerta">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @php
            $activo = old('activo', $empresa->exists ? ($empresa->activo ? '1' : '0') : '1');
        @endphp
        <form method="post" action="{{ $empresa->exists ? route('empresas.update', $empresa) : route('empresas.store') }}">
            @csrf
            @if ($empresa->exists) @method('put') @endif
            <div class="grid">
                <label>Nombre<input name="nombre" value="{{ old('nombre', $empresa->nombre) }}" required></label>
                <label>Responsable<input name="responsable" value="{{ old('responsable', $empresa->responsable) }}" required></label>
                <label>Fecha de inicio<input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', optional($empresa->fecha_inicio)->format('Y-m-d')) }}" required></label>
                <label class="check">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1" @checked((string) $activo === '1')>
                    Activa
                </label>
                <label class="ancho">Teléfonos<textarea name="telefonos" placeholder="Uno por línea">{{ old('telefonos', $empresa->telefonos) }}</textarea></label>
            </div>
            <h2>Usuario administrador</h2>
            <p>Este usuario entra a los contratos y las planillas de esta empresa.</p>
            <div class="grid">
                <label>Nombre<input name="admin_nombre" value="{{ old('admin_nombre', $admin->name ?? '') }}" required></label>
                <label>Correo<input type="email" name="admin_email" value="{{ old('admin_email', $admin->email ?? '') }}" required></label>
                <label>Contraseña<input type="password" name="admin_password" @unless($admin) required @endunless autocomplete="new-password"></label>
                <label>Confirmar contraseña<input type="password" name="admin_password_confirmation" @unless($admin) required @endunless autocomplete="new-password"></label>
            </div>
            @if ($admin)
                <p>Deje la contraseña en blanco si no desea cambiarla.</p>
            @endif
            <p><button type="submit">Guardar empresa</button></p>
        </form>
    </div>
@endsection
