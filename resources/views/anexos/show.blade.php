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
    @include('historial.resumen', [
        'creado' => null,
        'filtro' => ['planilla_rubro_id' => $ejecucion->id],
        'enlace' => route('historial.index', [$ejecucion->planilla->contrato, 'ejecucion' => $ejecucion->id]),
    ])

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
                <img src="{{ url('archivos/'.$imagen->ruta) }}" alt="Imagen">
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

    @php
        $enAplicacion = str_contains((string) request()->userAgent(), 'PlanillasApp');
        $tipoMedicion = \App\Services\UnidadMedicion::tipo($ejecucion->rubro->unidad);
        $explicaMedicion = \App\Services\UnidadMedicion::explica($tipoMedicion);
        $marcaMedicion = fn (string $campo) => \App\Services\UnidadMedicion::marca($tipoMedicion, $campo) ? 'cuenta' : '';
        $cantidad = fn ($valor) => $valor === null || $valor === '' ? '' : number_format((float) $valor, 2, '.', '');
        $etiquetaNumero = $tipoMedicion === 'm3km' ? 'Km' : 'Número';
    @endphp
    <style>
        table.hoja input.sub { width: 72px; }
        input.cuenta { background: #e7f6ec; }
    </style>
    <form method="post" action="{{ route('anexos.guardar', $ejecucion) }}" data-tipo="{{ $tipoMedicion }}">
        @csrf
        @if ($enAplicacion)
            @php
                $filasMovil = $anexo->lineas->values();
            @endphp
            <h2>Mediciones</h2>
            <p>{{ $explicaMedicion }}</p>
            <div data-lista>
                @foreach ($filasMovil as $i => $linea)
                    <article class="ficha" data-medicion>
                        <span class="ficha-num">{{ $i + 1 }}</span>
                        <label>Descripción<input name="lineas[{{ $i }}][descripcion]" value="{{ $linea->descripcion }}"></label>
                        <h3>Dimensiones</h3>
                        <div class="pares">
                            <label>Base 1<input class="n dim b1" name="lineas[{{ $i }}][base1]" value="{{ $linea->base1 !== null ? $linea->base1 + 0 : '' }}"></label>
                            <label>Base 2<input class="n dim b2" name="lineas[{{ $i }}][base2]" value="{{ $linea->base2 !== null ? $linea->base2 + 0 : '' }}"></label>
                            <label>Altura<input class="n dim altura" name="lineas[{{ $i }}][altura]" value="{{ $linea->altura !== null ? $linea->altura + 0 : '' }}"></label>
                            <label>{{ $etiquetaNumero }}<input class="n dim numero {{ $marcaMedicion('numero') }}" name="lineas[{{ $i }}][numero]" value="{{ $linea->numero !== null ? $linea->numero + 0 : '' }}"></label>
                        </div>
                        <h3>Subtotales</h3>
                        <div class="pares">
                            <label>Longitud<input class="n sub longitud {{ $marcaMedicion('longitud') }}" name="lineas[{{ $i }}][longitud]" value="{{ $cantidad($linea->longitud) }}"></label>
                            <label>Área<input class="n sub area {{ $marcaMedicion('area') }}" name="lineas[{{ $i }}][area]" value="{{ $cantidad($linea->area) }}"></label>
                            <label>Volumen<input class="n sub volumen {{ $marcaMedicion('volumen') }}" name="lineas[{{ $i }}][volumen]" value="{{ $cantidad($linea->volumen) }}"></label>
                        </div>
                        <label>Total<input class="n total {{ $marcaMedicion('total') }}" name="lineas[{{ $i }}][total]" value="{{ number_format((float) $linea->total, 2, '.', '') }}"></label>
                        <input type="hidden" class="flag-total" name="lineas[{{ $i }}][manual_total]" value="0">
                    </article>
                @endforeach
                @php
                    $i = $filasMovil->count();
                @endphp
                <article class="ficha" data-medicion>
                    <span class="ficha-num">{{ $i + 1 }}</span>
                    <label>Descripción<input name="lineas[{{ $i }}][descripcion]" placeholder="Nueva medición"></label>
                    <h3>Dimensiones</h3>
                    <div class="pares">
                        <label>Base 1<input class="n dim b1" name="lineas[{{ $i }}][base1]"></label>
                        <label>Base 2<input class="n dim b2" name="lineas[{{ $i }}][base2]"></label>
                        <label>Altura<input class="n dim altura" name="lineas[{{ $i }}][altura]"></label>
                        <label>{{ $etiquetaNumero }}<input class="n dim numero {{ $marcaMedicion('numero') }}" name="lineas[{{ $i }}][numero]"></label>
                    </div>
                    <h3>Subtotales</h3>
                    <div class="pares">
                        <label>Longitud<input class="n sub longitud {{ $marcaMedicion('longitud') }}" name="lineas[{{ $i }}][longitud]"></label>
                        <label>Área<input class="n sub area {{ $marcaMedicion('area') }}" name="lineas[{{ $i }}][area]"></label>
                        <label>Volumen<input class="n sub volumen {{ $marcaMedicion('volumen') }}" name="lineas[{{ $i }}][volumen]"></label>
                    </div>
                    <label>Total<input class="n total {{ $marcaMedicion('total') }}" name="lineas[{{ $i }}][total]"></label>
                    <input type="hidden" class="flag-total" name="lineas[{{ $i }}][manual_total]" value="0">
                </article>
            </div>
            <p><button type="button" class="secundario" id="agregar-medicion">Agregar medición</button></p>
            <template id="medicion-nueva">
                <article class="ficha" data-medicion>
                    <span class="ficha-num"></span>
                    <label>Descripción<input name="lineas[__i__][descripcion]" placeholder="Nueva medición"></label>
                    <h3>Dimensiones</h3>
                    <div class="pares">
                        <label>Base 1<input class="n dim b1" name="lineas[__i__][base1]"></label>
                        <label>Base 2<input class="n dim b2" name="lineas[__i__][base2]"></label>
                        <label>Altura<input class="n dim altura" name="lineas[__i__][altura]"></label>
                        <label>{{ $etiquetaNumero }}<input class="n dim numero {{ $marcaMedicion('numero') }}" name="lineas[__i__][numero]"></label>
                    </div>
                    <h3>Subtotales</h3>
                    <div class="pares">
                        <label>Longitud<input class="n sub longitud {{ $marcaMedicion('longitud') }}" name="lineas[__i__][longitud]"></label>
                        <label>Área<input class="n sub area {{ $marcaMedicion('area') }}" name="lineas[__i__][area]"></label>
                        <label>Volumen<input class="n sub volumen {{ $marcaMedicion('volumen') }}" name="lineas[__i__][volumen]"></label>
                    </div>
                    <label>Total<input class="n total {{ $marcaMedicion('total') }}" name="lineas[__i__][total]"></label>
                    <input type="hidden" class="flag-total" name="lineas[__i__][manual_total]" value="0">
                </article>
            </template>
        @else
        <div class="card">
            <h2>Mediciones</h2>
            <p>{{ $explicaMedicion }}</p>
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
                            <th>{{ $etiquetaNumero }}</th>
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
                                <td><input class="n dim numero {{ $marcaMedicion('numero') }}" name="lineas[{{ $i }}][numero]" value="{{ $linea->numero !== null ? $linea->numero + 0 : '' }}"></td>
                                <td><input class="n sub longitud {{ $marcaMedicion('longitud') }}" name="lineas[{{ $i }}][longitud]" value="{{ $cantidad($linea->longitud) }}"></td>
                                <td><input class="n sub area {{ $marcaMedicion('area') }}" name="lineas[{{ $i }}][area]" value="{{ $cantidad($linea->area) }}"></td>
                                <td><input class="n sub volumen {{ $marcaMedicion('volumen') }}" name="lineas[{{ $i }}][volumen]" value="{{ $cantidad($linea->volumen) }}"></td>
                                <td><input class="n total {{ $marcaMedicion('total') }}" name="lineas[{{ $i }}][total]" value="{{ number_format((float) $linea->total, 2, '.', '') }}"><input type="hidden" class="flag-total" name="lineas[{{ $i }}][manual_total]" value="0"></td>
                            </tr>
                        @endforeach
                        @for ($n = 0; $n < 12; $n++)
                            @php($i = $filas->count() + $n)
                            <tr>
                                <td><input name="lineas[{{ $i }}][descripcion]"></td>
                                <td><input class="n dim b1" name="lineas[{{ $i }}][base1]"></td>
                                <td><input class="n dim b2" name="lineas[{{ $i }}][base2]"></td>
                                <td><input class="n dim altura" name="lineas[{{ $i }}][altura]"></td>
                                <td><input class="n dim numero {{ $marcaMedicion('numero') }}" name="lineas[{{ $i }}][numero]"></td>
                                <td><input class="n sub longitud {{ $marcaMedicion('longitud') }}" name="lineas[{{ $i }}][longitud]"></td>
                                <td><input class="n sub area {{ $marcaMedicion('area') }}" name="lineas[{{ $i }}][area]"></td>
                                <td><input class="n sub volumen {{ $marcaMedicion('volumen') }}" name="lineas[{{ $i }}][volumen]"></td>
                                <td><input class="n total {{ $marcaMedicion('total') }}" name="lineas[{{ $i }}][total]"><input type="hidden" class="flag-total" name="lineas[{{ $i }}][manual_total]" value="0"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
        @endif
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
                <img src="{{ url('archivos/'.$imagen->ruta) }}" alt="Otra imagen">
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
        const tipoMedicion = document.querySelector('form[data-tipo]')?.dataset.tipo || 'numero';
        const valor = (campo) => !campo || campo.value === '' ? null : parseFloat(campo.value);
        const texto = (numero) => numero === null ? '' : (Math.round((numero + Number.EPSILON) * 100) / 100).toFixed(2);
        const enlazarMedicion = (fila) => {
            const b1 = fila.querySelector('.b1');
            const b2 = fila.querySelector('.b2');
            const altura = fila.querySelector('.altura');
            const numero = fila.querySelector('.numero');
            const longitud = fila.querySelector('.longitud');
            const area = fila.querySelector('.area');
            const volumen = fila.querySelector('.volumen');
            const total = fila.querySelector('.total');
            if (!b1 || !total || !longitud || !area || !volumen) return;
            const poner = (campo, numeroCalculado) => {
                if (!campo || campo.dataset.manual === '1') return;
                campo.value = numeroCalculado === null ? '' : texto(numeroCalculado);
            };
            const pintar = () => {
                const base1 = valor(b1);
                const base2 = valor(b2);
                const alto = valor(altura);
                const veces = valor(numero);
                const factor = (tipoMedicion === 'm3km' || tipoMedicion === 'kg') ? 1 : (veces === null ? 1 : veces);
                const lon = base1 === null ? null : base1 * factor;
                let ar = null;
                if (base1 !== null && base2 !== null) ar = base1 * base2 * factor;
                else if (base1 !== null && alto !== null) ar = base1 * alto * factor;
                const vol = (base1 !== null && base2 !== null && alto !== null) ? base1 * base2 * alto * factor : null;
                poner(longitud, lon);
                poner(area, ar);
                poner(volumen, vol);
                if (total.dataset.manual === '1') return;
                const leido = (campo) => valor(campo);
                let elegido = null;
                if (tipoMedicion === 'longitud') elegido = leido(longitud);
                else if (tipoMedicion === 'area') elegido = leido(area);
                else if (tipoMedicion === 'volumen') elegido = leido(volumen);
                else if (tipoMedicion === 'm3km') elegido = (leido(volumen) === null || veces === null) ? null : leido(volumen) * veces;
                else elegido = veces;
                total.value = elegido === null ? '' : texto(elegido);
            };
            [b1, b2, altura, numero].forEach((campo) => campo.addEventListener('input', pintar));
            [longitud, area, volumen].forEach((campo) => campo.addEventListener('input', () => {
                campo.dataset.manual = '1';
                pintar();
            }));
            total.addEventListener('input', () => {
                total.dataset.manual = '1';
                const marca = fila.querySelector('.flag-total');
                if (marca) marca.value = '1';
            });
        };
        document.querySelectorAll('table.hoja tbody tr').forEach(enlazarMedicion);
        const listaMediciones = document.querySelector('[data-lista]');
        const botonMedicion = document.getElementById('agregar-medicion');
        if (listaMediciones && botonMedicion) {
            listaMediciones.querySelectorAll('[data-medicion]').forEach(enlazarMedicion);
            let indiceMedicion = listaMediciones.querySelectorAll('[data-medicion]').length;
            botonMedicion.addEventListener('click', () => {
                const fila = document.getElementById('medicion-nueva').content.cloneNode(true).querySelector('[data-medicion]');
                fila.querySelectorAll('[name]').forEach((campo) => {
                    campo.name = campo.name.replace('__i__', String(indiceMedicion));
                });
                indiceMedicion += 1;
                fila.querySelector('.ficha-num').textContent = String(indiceMedicion);
                listaMediciones.appendChild(fila);
                enlazarMedicion(fila);
                fila.querySelector('input')?.focus();
            });
        }
        const claveMedicion = (campo) => ['b1', 'b2', 'altura', 'numero', 'longitud', 'area', 'volumen', 'total'].find((clase) => campo.classList.contains(clase))
            || (campo.name.includes('[descripcion]') ? 'descripcion' : campo.name);
        const camposMedicion = (fila) => [...fila.querySelectorAll('input:not([type="hidden"])')];
        const enfocarMedicion = (campo) => {
            if (!campo) return;
            campo.focus();
            campo.select();
            campo.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        };
        document.querySelectorAll('table.hoja tbody, [data-lista]').forEach((lista) => {
            const filasVisibles = () => [...lista.querySelectorAll('tr, [data-medicion]')].filter((fila) => !fila.hidden);
            lista.addEventListener('keydown', (evento) => {
                const campo = evento.target;
                if (!(campo instanceof HTMLInputElement) || campo.type === 'hidden' || evento.altKey || evento.ctrlKey || evento.metaKey) return;
                const horizontal = evento.key === 'ArrowLeft' || evento.key === 'ArrowRight';
                const vertical = evento.key === 'ArrowUp' || evento.key === 'ArrowDown';
                if (!horizontal && !vertical) return;
                if (horizontal && campo.selectionStart != null) {
                    const completo = campo.selectionStart === 0 && campo.selectionEnd === campo.value.length;
                    const izquierda = evento.key === 'ArrowLeft' && campo.selectionStart === 0 && campo.selectionEnd === 0;
                    const derecha = evento.key === 'ArrowRight' && campo.selectionStart === campo.value.length;
                    if (!completo && !izquierda && !derecha) return;
                }
                const fila = campo.closest('[data-medicion], tr');
                const filas = filasVisibles();
                const posicion = filas.indexOf(fila);
                if (!fila || posicion < 0) return;
                evento.preventDefault();
                const deltaFila = evento.key === 'ArrowUp' ? -1 : (evento.key === 'ArrowDown' ? 1 : 0);
                const deltaCampo = evento.key === 'ArrowLeft' ? -1 : (evento.key === 'ArrowRight' ? 1 : 0);
                const destinoFila = filas[posicion + deltaFila];
                if (!destinoFila) return;
                const destinos = camposMedicion(destinoFila);
                if (deltaFila !== 0) {
                    enfocarMedicion(destinos.find((destino) => claveMedicion(destino) === claveMedicion(campo)));
                    return;
                }
                enfocarMedicion(destinos[camposMedicion(fila).indexOf(campo) + deltaCampo]);
            });
        });
    </script>
@endsection
