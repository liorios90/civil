@extends('layout')

@section('titulo', 'Hoja rubro '.$ejecucion->rubro->numero)

@section('contenido')
    <p>
        <a href="{{ route('frentes.show', $ejecucion->rubro->frente) }}">Volver al frente</a>
        · <a href="{{ route('impresion.anexo', $ejecucion) }}" target="_blank">Imprimir hoja</a>
    </p>
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <div class="card">
        <strong>Rubro {{ $ejecucion->rubro->numero }}.</strong> {{ $ejecucion->rubro->descripcion }}
        · Unidad {{ $ejecucion->rubro->unidad }}
        · Total a facturar: {{ number_format((float) $ejecucion->cantidad_actual, 2, '.', '') }}
    </div>

    @php
        $imagenesOtras = $anexo->imagenes->filter(fn ($imagen) => str_starts_with($imagen->ruta, 'anexos/otras/'));
        $imagenesIniciales = $anexo->imagenes->reject(fn ($imagen) => str_starts_with($imagen->ruta, 'anexos/otras/'));
    @endphp

    <form method="post" action="{{ route('anexos.imagenes', $ejecucion) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="seccion" value="inicial">
        <div class="card">
            <h2>Imágenes</h2>
            <p><label>Cargar imágenes<br><input type="file" name="imagenes[]" accept="image/*" multiple></label></p>
            <button type="submit">Guardar imágenes</button>
        </div>
    </form>
    <div class="galeria">
        @forelse ($imagenesIniciales as $imagen)
            <figure>
                <img src="{{ asset('storage/'.$imagen->ruta) }}" alt="Imagen">
                <form method="post" action="{{ route('anexos.imagenes.destroy', [$ejecucion, $imagen]) }}">
                    @csrf
                    @method('delete')
                    <button class="danger" type="submit">Quitar</button>
                </form>
            </figure>
        @empty
            <p>Todavía no hay imágenes en esta sección.</p>
        @endforelse
    </div>

    <form method="post" action="{{ route('anexos.guardar', $ejecucion) }}">
        @csrf
        <div class="card">
            <h2>Mediciones</h2>
            <div class="scroll">
                <table class="hoja">
                    <thead>
                        <tr>
                            <th rowspan="2">Descripción</th>
                            <th colspan="4">Dimensiones</th>
                            <th colspan="3">Subtotales</th>
                            <th rowspan="2">Total</th>
                        </tr>
                        <tr>
                            <th>Base 1</th>
                            <th>Base 2</th>
                            <th>Altura</th>
                            <th>Número</th>
                            <th>Longitud</th>
                            <th>Área</th>
                            <th>Volumen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($filas = $anexo->lineas->values())
                        @foreach ($filas as $i => $linea)
                            <tr>
                                <td><input name="lineas[{{ $i }}][descripcion]" value="{{ $linea->descripcion }}"></td>
                                <td><input class="n dim b1" name="lineas[{{ $i }}][base1]" value="{{ $linea->base1 !== null ? $linea->base1 + 0 : '' }}"></td>
                                <td><input class="n dim b2" name="lineas[{{ $i }}][base2]" value="{{ $linea->base2 !== null ? $linea->base2 + 0 : '' }}"></td>
                                <td><input class="n dim altura" name="lineas[{{ $i }}][altura]" value="{{ $linea->altura !== null ? $linea->altura + 0 : '' }}"></td>
                                <td><input class="n dim numero" name="lineas[{{ $i }}][numero]" value="{{ $linea->numero !== null ? $linea->numero + 0 : '' }}"></td>
                                <td class="calc longitud">{{ $linea->longitud !== null ? number_format((float) $linea->longitud, 2, '.', '') : '' }}</td>
                                <td class="calc area">{{ $linea->area !== null ? number_format((float) $linea->area, 2, '.', '') : '' }}</td>
                                <td class="calc volumen">{{ $linea->volumen !== null ? number_format((float) $linea->volumen, 2, '.', '') : '' }}</td>
                                <td><input class="n total" name="lineas[{{ $i }}][total]" value="{{ number_format((float) $linea->total, 2, '.', '') }}"></td>
                            </tr>
                        @endforeach
                        @for ($n = 0; $n < 12; $n++)
                            @php($i = $filas->count() + $n)
                            <tr>
                                <td><input name="lineas[{{ $i }}][descripcion]"></td>
                                <td><input class="n dim b1" name="lineas[{{ $i }}][base1]"></td>
                                <td><input class="n dim b2" name="lineas[{{ $i }}][base2]"></td>
                                <td><input class="n dim altura" name="lineas[{{ $i }}][altura]"></td>
                                <td><input class="n dim numero" name="lineas[{{ $i }}][numero]"></td>
                                <td class="calc longitud"></td>
                                <td class="calc area"></td>
                                <td class="calc volumen"></td>
                                <td><input class="n total" name="lineas[{{ $i }}][total]"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
        <div class="barra"><button type="submit">Guardar mediciones</button></div>
    </form>

    <form method="post" action="{{ route('anexos.imagenes', $ejecucion) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="seccion" value="otras">
        <div class="card">
            <h2>Otras imágenes</h2>
            <p><label>Cargar imágenes<br><input type="file" name="imagenes[]" accept="image/*" multiple></label></p>
            <button type="submit">Guardar imágenes</button>
        </div>
    </form>
    <div class="galeria">
        @forelse ($imagenesOtras as $imagen)
            <figure>
                <img src="{{ asset('storage/'.$imagen->ruta) }}" alt="Otra imagen">
                <form method="post" action="{{ route('anexos.imagenes.destroy', [$ejecucion, $imagen]) }}">
                    @csrf
                    @method('delete')
                    <button class="danger" type="submit">Quitar</button>
                </form>
            </figure>
        @empty
            <p>Todavía no hay otras imágenes.</p>
        @endforelse
    </div>

    <script>
        const valor = (campo) => campo.value === '' ? null : parseFloat(campo.value);
        const texto = (numero) => numero === null ? '' : (Math.round((numero + Number.EPSILON) * 100) / 100).toFixed(2);
        document.querySelectorAll('table.hoja tbody tr').forEach((fila) => {
            const b1 = fila.querySelector('.b1');
            const b2 = fila.querySelector('.b2');
            const altura = fila.querySelector('.altura');
            const numero = fila.querySelector('.numero');
            const total = fila.querySelector('.total');
            const pintar = () => {
                const base1 = valor(b1);
                const base2 = valor(b2);
                const alto = valor(altura);
                const veces = valor(numero);
                const factor = veces === null ? 1 : veces;
                const longitud = base1 === null ? null : base1 * factor;
                let area = null;
                if (base1 !== null && base2 !== null) area = base1 * base2 * factor;
                else if (base1 !== null && alto !== null) area = base1 * alto * factor;
                const volumen = (base1 !== null && base2 !== null && alto !== null) ? base1 * base2 * alto * factor : null;
                fila.querySelector('.longitud').textContent = texto(longitud);
                fila.querySelector('.area').textContent = texto(area);
                fila.querySelector('.volumen').textContent = texto(volumen);
                if (total.dataset.manual === '1') return;
                const elegido = volumen ?? area ?? longitud ?? veces;
                total.value = elegido === null ? '' : texto(elegido);
            };
            [b1, b2, altura, numero].forEach((campo) => campo.addEventListener('input', pintar));
            total.addEventListener('input', () => { total.dataset.manual = '1'; });
        });
    </script>
@endsection
