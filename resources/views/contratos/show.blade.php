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
    @if ($planilla)
        @php
            $estadoPlanilla = $planilla->estado ?: 'borrador';
        @endphp
        <div class="card fila">
            <div>
                <h2>Planilla {{ $planilla->numero }}</h2>
                <p><span class="estado-planilla {{ $estadoPlanilla }}">{{ ['borrador' => 'En elaboración', 'pendiente' => 'Pendiente de aprobación', 'aprobada' => 'Aprobada'][$estadoPlanilla] ?? $estadoPlanilla }}</span></p>
            </div>
            <div class="acciones">
                @if ($estadoPlanilla === 'borrador')
                    <form method="post" action="{{ route('contratos.enviar', $contrato) }}">
                        @csrf
                        <button type="submit">Enviar a aprobación</button>
                    </form>
                @endif
                @if (auth()->user()->esAdministrador() && $estadoPlanilla === 'pendiente')
                    <form method="post" action="{{ route('contratos.aprobar', $contrato) }}">
                        @csrf
                        <button type="submit">Aprobar planilla</button>
                    </form>
                @endif
                @if (auth()->user()->esAdministrador() && in_array($estadoPlanilla, ['pendiente', 'aprobada'], true))
                    <form method="post" action="{{ route('contratos.devolver', $contrato) }}">
                        @csrf
                        <button class="secundario" type="submit">Devolver a elaboración</button>
                    </form>
                @endif
                @if (auth()->user()->esAdministrador() && $estadoPlanilla === 'aprobada')
                    <form method="post" action="{{ route('contratos.siguiente', $contrato) }}" onsubmit="return confirm('Se abre la planilla siguiente. El anterior será el total de esta planilla y este período empieza en cero.')">
                        @csrf
                        <button type="submit">Abrir planilla {{ str_pad((string) ((int) $planilla->numero + 1), 2, '0', STR_PAD_LEFT) }}</button>
                    </form>
                @endif
                @if (auth()->user()->esAdministrador())
                    <form method="post" action="{{ route('contratos.planillas.eliminar', [$contrato, $planilla]) }}" onsubmit="return confirm('{{ $contrato->planillas->count() > 1 ? '¿Eliminar la planilla '.$planilla->numero.'? Se borran sus cantidades y sus hojas de medición. Las anteriores quedan igual.' : '¿Eliminar la planilla '.$planilla->numero.'? Se borran sus cantidades y sus hojas. El contrato queda con una planilla 01 vacía.' }}')">
                        @csrf
                        @method('delete')
                        <button class="btn-rojo" type="submit">Eliminar planilla</button>
                    </form>
                @endif
            </div>
        </div>
        @if ($contrato->planillas->count() > 1)
            <div class="card">
                <h2>Planillas del contrato</h2>
                @foreach ($contrato->planillas->sortBy('id') as $periodo)
                    @php $estadoPeriodo = $periodo->estado ?: 'borrador'; @endphp
                    <p class="fila">
                        <span>Planilla {{ $periodo->numero }}</span>
                        <span class="estado-planilla {{ $estadoPeriodo }}">{{ ['borrador' => 'En elaboración', 'pendiente' => 'Pendiente de aprobación', 'aprobada' => 'Aprobada'][$estadoPeriodo] ?? $estadoPeriodo }}</span>
                        <a href="{{ route('impresion.planilla', $periodo) }}" target="_blank">Imprimir</a>
                        <a href="{{ route('impresion.excel', $periodo) }}">Excel</a>
                    </p>
                @endforeach
            </div>
        @endif
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
