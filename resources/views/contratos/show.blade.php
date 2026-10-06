@extends('layout')

@section('titulo', $contrato->codigo_proceso)

@section('contenido')
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <p><a class="btn secundario" href="{{ route('inicio') }}">Volver a la pantalla principal</a></p>
    <div class="card fila">
        <div>
            <h1>{{ $contrato->entidad }}</h1>
            <p>{{ $contrato->objeto }}</p>
        </div>
        <div class="acciones">
            @if (auth()->user()->esAdministrador())
                <a class="btn" href="{{ route('contratos.edit', $contrato) }}">Rubros</a>
            @endif
            @if ($planilla)
                <a class="btn" href="{{ route('impresion.planilla', $planilla) }}" target="_blank">Imprimir planilla</a>
                <a class="btn" href="{{ route('impresion.excel', $planilla) }}">Exportar Excel</a>
                <a class="btn" href="{{ route('avance.comparacion', $planilla) }}">Comparar avance</a>
            @endif
            <a class="btn" href="{{ route('historial.index', $contrato) }}">Historial</a>
        </div>
    </div>
    @if ($planilla && $contrato->planillas->count() > 1)
            <div class="card">
                <h2>Planillas del contrato</h2>
                @foreach ($contrato->planillas->sortBy('id') as $periodo)
                    <p class="fila">
                        <span>Planilla {{ $periodo->numero }}</span>
                        <a href="{{ route('impresion.planilla', $periodo) }}" target="_blank">Imprimir</a>
                        <a href="{{ route('impresion.excel', $periodo) }}">Excel</a>
                    </p>
                @endforeach
            </div>
    @endif
    <div class="card">
        <h2>Enlace para el fiscalizador</h2>
        <p>Quien tenga este enlace ve la planilla, las hojas de medición y las fotos. No puede modificar nada ni necesita un usuario.</p>
        @if ($contrato->enlace_fiscalizador)
            <p class="fila">
                <input id="enlace-fiscalizador" value="{{ route('fiscalizacion.show', $contrato->enlace_fiscalizador) }}" readonly>
                <button type="button" class="secundario" id="copiar-enlace">Copiar enlace</button>
                <a class="btn" href="https://wa.me/?text={{ rawurlencode('Planilla '.$contrato->codigo_proceso.' '.route('fiscalizacion.show', $contrato->enlace_fiscalizador)) }}" target="_blank">WhatsApp</a>
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
    <div class="card">
        <h2>Nueva planilla</h2>
        <form method="post" action="{{ route('frentes.store', $contrato) }}" class="fila">
            @csrf
            <input name="nombre" value="{{ old('nombre', 'planilla '.($contrato->frentes->count() + 1)) }}" required>
            <button type="submit">Crear planilla</button>
        </form>
        @error('nombre')
            <p>{{ $message }}</p>
        @enderror
    </div>
    <div class="card">
        <h2>Planillas</h2>
        <p>Cada planilla guarda sus rubros, cantidades y hojas de medición.</p>
        <div class="opciones">
            @php($ultimoFrente = auth()->user()->esAdministrador() ? $contrato->frentes->sortBy([['orden', 'desc'], ['id', 'desc']])->first() : null)
            @forelse ($contrato->frentes as $frente)
                <div class="opcion">
                    <a href="{{ route('frentes.show', $frente) }}">
                        <strong>{{ $frente->nombre }}</strong>
                        <small>{{ $frente->rubros->count() }} rubros</small>
                    </a>
                    @if ($ultimoFrente && $frente->id === $ultimoFrente->id)
                        <form method="post" action="{{ route('frentes.destroy', $frente) }}" onsubmit="return confirm('¿Eliminar {{ $frente->nombre }} y sus cantidades? Solo se puede eliminar la última planilla.')">
                            @csrf
                            @method('delete')
                            <button class="btn-rojo" type="submit">Eliminar</button>
                        </form>
                    @endif
                </div>
            @empty
                <p>Todavía no hay planillas. Crea la primera arriba.</p>
            @endforelse
        </div>
    </div>
@endsection
