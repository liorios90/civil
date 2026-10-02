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
            <a class="btn" href="{{ route('historial.index', $contrato) }}">Historial</a>
        </div>
    </div>
    <div class="card">
        <h2>Enlace para el fiscalizador</h2>
        <p>Quien tenga este enlace ve la planilla, las hojas de medición y las fotos. No puede modificar nada ni necesita un usuario.</p>
        @if ($contrato->enlace_fiscalizador)
            @php
                $urlFiscalizador = route('fiscalizacion.show', $contrato->enlace_fiscalizador);
            @endphp
            <p class="fila">
                <input id="enlace-fiscalizador" value="{{ $urlFiscalizador }}" readonly>
                <button type="button" class="secundario" id="copiar-enlace">Copiar enlace</button>
                <a class="btn" href="https://wa.me/?text={{ rawurlencode('Planilla '.$contrato->codigo_proceso."\n".$urlFiscalizador) }}" target="_blank">WhatsApp</a>
            </p>
            <form method="post" action="{{ route('contratos.enlace', $contrato) }}" onsubmit="return confirm('El enlace actual dejará de funcionar. ¿Generar otro?')">
                @csrf
                <input type="hidden" name="regenerar" value="1">
                <button class="secundario" type="submit">Generar otro enlace</button>
            </form>
            <script>
                document.getElementById('copiar-enlace').addEventListener('click', async () => {
                    const campo = document.getElementById('enlace-fiscalizador');
                    try {
                        await navigator.clipboard.writeText(campo.value);
                    } catch (error) {
                        campo.select();
                        document.execCommand('copy');
                    }
                    const boton = document.getElementById('copiar-enlace');
                    boton.textContent = 'Enlace copiado';
                });
            </script>
        @else
            <form method="post" action="{{ route('contratos.enlace', $contrato) }}">
                @csrf
                <button type="submit">Crear enlace</button>
            </form>
        @endif
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
