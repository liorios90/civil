@extends('layout')

@section('titulo', $frente->nombre)

@section('contenido')
    <p><a href="{{ route('contratos.show', $frente->contrato) }}">{{ $frente->contrato->codigo_proceso }}</a></p>
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    @php
        $gestiona = auth()->user()->esAdministrador();
        $esUltima = $gestiona && $frente->contrato->frentes()->reorder()->orderByDesc('orden')->orderByDesc('id')->value('id') === $frente->id;
    @endphp
    @if ($gestiona)
        <form method="post" action="{{ route('frentes.update', $frente) }}" class="fila card">
            @csrf
            @method('put')
            <input name="nombre" value="{{ $frente->nombre }}" required>
            <button type="submit">Guardar nombre</button>
            @if ($esUltima)
                <button class="btn-rojo" type="submit" form="eliminar-frente">Eliminar planilla</button>
            @endif
        </form>
        @if ($esUltima)
            <form id="eliminar-frente" method="post" action="{{ route('frentes.destroy', $frente) }}" onsubmit="return confirm('¿Eliminar esta planilla y sus cantidades?')">
                @csrf
                @method('delete')
            </form>
        @endif
    @else
        <div class="card"><h1>{{ $frente->nombre }}</h1></div>
    @endif
    @include('historial.resumen', [
        'creado' => $frente,
        'filtro' => ['frente_id' => $frente->id],
        'enlace' => route('historial.index', [$frente->contrato, 'frente' => $frente->id]),
    ])
    @php
        $m = fn ($v) => number_format((float) $v, 2);
        $q = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $enAplicacion = str_contains((string) request()->userAgent(), 'PlanillasApp');
    @endphp
    <!-- @if ($planilla)
        <p>Planilla {{ $planilla->numero }}. El anterior es el total arrastrado de la planilla anterior y no se escribe a mano.</p>
    @endif -->
    <form method="post" action="{{ route('rubros.guardar', $frente) }}">
        @csrf
        @if ($enAplicacion)
            <h2>{{ $frente->nombre }}</h2>
            <p class="buscar"><input type="search" data-buscar-rubros placeholder="Buscar rubro por número o descripción" autocomplete="off"></p>
            <p data-sin-rubros hidden>Ningún rubro coincide.</p>
            <div data-lista>
                @forelse ($lineas as $i => $linea)
                    @php
                        $rubro = $linea['rubro'];
                        $ejecucion = $linea['ejecucion'];
                        $k = $linea['calculo'];
                    @endphp
                    <article class="ficha" data-fila>
                        <div class="ficha-titulo">
                            <span class="ficha-num">{{ $rubro->numero }}</span>
                            @if ($ejecucion)
                                <a href="{{ route('anexos.show', $ejecucion) }}">{{ $rubro->descripcion }}</a>
                            @else
                                <strong>{{ $rubro->descripcion }}</strong>
                            @endif
                            <input type="hidden" name="filas[{{ $i }}][id]" value="{{ $rubro->id }}">
                        </div>
                        @if ($gestiona)
                            <p><button class="danger" type="submit" form="quitar-rubro-{{ $rubro->id }}">Quitar</button></p>
                        @endif
                        <label>Unidad<input class="u" name="filas[{{ $i }}][unidad]" value="{{ $rubro->unidad }}"></label>
                        <h3>Contratado</h3>
                        <div class="pares">
                            <label>Cantidad <b class="contratada">{{ $q($k['cantidad_contratada']) }}</b></label>
                            <label>Unitario <b class="unitario">{{ $q($k['precio_unitario']) }}</b></label>
                        </div>
                        <p class="resultado">Total contratado <b class="total-contratado">{{ $m($k['total_contratado']) }}</b></p>
                        <h3>Cantidades ejecutadas</h3>
                        <div class="pares">
                            <label>Anterior <b class="anterior">{{ $q($k['cantidad_anterior']) }}</b></label>
                            <label>Actual <b class="actual">{{ $q($k['cantidad_actual']) }}</b></label>
                        </div>
                        <p class="resultado">Total cantidad <b class="total-cantidad">{{ $m($k['cantidad_total']) }}</b></p>
                        <h3>Total en dólares</h3>
                        <div class="pares">
                            <p class="resultado">Anterior <b class="valor-anterior">{{ $m($k['valor_anterior']) }}</b></p>
                            <p class="resultado">Actual <b class="valor-actual">{{ $m($k['valor_actual']) }}</b></p>
                        </div>
                        <p class="resultado">Total <b class="valor-total">{{ $m($k['valor_total']) }}</b></p>
                    </article>
                @empty
                    <p class="vacia">Esta planilla no tiene rubros. Agrega uno abajo.</p>
                @endforelse
                @if ($gestiona)
                @for ($n = 0; $n < 1; $n++)
                    @php $i = count($lineas) + $n; @endphp
                    <article class="ficha" data-fila data-nuevo>
                        <label>Descripción<input name="filas[{{ $i }}][descripcion]" placeholder="Nuevo rubro"></label>
                        <label>Unidad<input class="u" name="filas[{{ $i }}][unidad]" placeholder="u"></label>
                        <h3>Contratado</h3>
                        <div class="pares">
                            <label>Cantidad<input class="n contratada" name="filas[{{ $i }}][cantidad_contratada]"></label>
                            <label>Unitario<input class="n unitario" name="filas[{{ $i }}][precio_unitario]"></label>
                        </div>
                        <p class="resultado">Total contratado <b class="total-contratado">0.00</b></p>
                        <h3>Cantidades ejecutadas</h3>
                        <div class="pares">
                            <label>Anterior <b class="anterior">0</b></label>
                            <label>Actual <b class="actual">0</b></label>
                        </div>
                        <p class="resultado">Total cantidad <b class="total-cantidad">0.00</b></p>
                        <h3>Total en dólares</h3>
                        <div class="pares">
                            <p class="resultado">Anterior <b class="valor-anterior">0.00</b></p>
                            <p class="resultado">Actual <b class="valor-actual">0.00</b></p>
                        </div>
                        <p class="resultado">Total <b class="valor-total">0.00</b></p>
                    </article>
                @endfor
                @endif
                @if ($lineas !== [])
                    <article class="ficha cierre-ficha" data-cierre>
                        <h3>Trabajos realizados</h3>
                        <p>Contratado <b class="pie-contratado">{{ $m($totales['contratado']) }}</b></p>
                        <p>Anterior <b class="pie-anterior">{{ $m($totales['anterior']) }}</b></p>
                        <p>Actual <b class="pie-actual">{{ $m($totales['actual']) }}</b></p>
                        <p>Total <b class="pie-acumulado">{{ $m($totales['acumulado']) }}</b></p>
                    </article>
                @endif
            </div>
        @else
        <div class="card">
            <h2>{{ $frente->nombre }}</h2>
            <p class="buscar"><input type="search" data-buscar-rubros placeholder="Buscar rubro por número o descripción" autocomplete="off"></p>
            <p data-sin-rubros hidden>Ningún rubro coincide.</p>
            <div class="scroll ajustada">
                <table class="hoja vista">
                    <colgroup>
                        <col style="width:4%">
                        <col>
                        <col style="width:4%">
                        <col style="width:8%">
                        <col style="width:8%">
                        <col style="width:7%">
                        <col style="width:7%">
                        <col style="width:7%">
                        <col style="width:7%">
                        <col style="width:8%">
                        <col style="width:8%">
                        <col style="width:8%">
                        <col style="width:6%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th rowspan="2">No.</th>
                            <th rowspan="2">Descripción</th>
                            <th rowspan="2">U</th>
                            <th colspan="3">Contratado</th>
                            <th colspan="3">Cantidades ejecutadas</th>
                            <th colspan="3">Total en dólares</th>
                            <th rowspan="2"></th>
                        </tr>
                        <tr>
                            <th>Cantidad</th>
                            <th>Unitario</th>
                            <th>Total</th>
                            <th>Anterior</th>
                            <th>Actual</th>
                            <th>Total</th>
                            <th>Anterior</th>
                            <th>Actual</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody data-lista>
                        @forelse ($lineas as $i => $linea)
                            @php
                                $rubro = $linea['rubro'];
                                $ejecucion = $linea['ejecucion'];
                                $k = $linea['calculo'];
                            @endphp
                            <tr data-fila>
                                <td class="num">{{ $rubro->numero }}<input type="hidden" name="filas[{{ $i }}][id]" value="{{ $rubro->id }}"></td>
                                <td>
                                    @if ($ejecucion)
                                        <a href="{{ route('anexos.show', $ejecucion) }}">{{ $rubro->descripcion }}</a>
                                    @else
                                        {{ $rubro->descripcion }}
                                    @endif
                                </td>
                                <td><input class="u" name="filas[{{ $i }}][unidad]" value="{{ $rubro->unidad }}"></td>
                                <td class="num contratada">{{ $q($k['cantidad_contratada']) }}</td>
                                <td class="num unitario">{{ $q($k['precio_unitario']) }}</td>
                                <td class="num total-contratado">{{ $m($k['total_contratado']) }}</td>
                                <td class="num anterior">{{ $q($k['cantidad_anterior']) }}</td>
                                <td class="num actual">{{ $q($k['cantidad_actual']) }}</td>
                                <td class="num total-cantidad">{{ $m($k['cantidad_total']) }}</td>
                                <td class="num valor-anterior">{{ $m($k['valor_anterior']) }}</td>
                                <td class="num valor-actual">{{ $m($k['valor_actual']) }}</td>
                                <td class="num valor-total">{{ $m($k['valor_total']) }}</td>
                                <td>@if ($gestiona)<button class="danger" type="submit" form="quitar-rubro-{{ $rubro->id }}">Quitar</button>@endif</td>
                            </tr>
                        @empty
                            <tr class="vacia"><td colspan="13">Esta planilla no tiene rubros. Agrega uno abajo.</td></tr>
                        @endforelse
                        @if ($gestiona)
                        @for ($n = 0; $n < 3; $n++)
                            @php $i = count($lineas) + $n; @endphp
                            <tr data-fila data-nuevo>
                                <td class="num"></td>
                                <td><input name="filas[{{ $i }}][descripcion]" placeholder="Nuevo rubro"></td>
                                <td><input class="u" name="filas[{{ $i }}][unidad]" placeholder="u"></td>
                                <td><input class="n contratada" name="filas[{{ $i }}][cantidad_contratada]"></td>
                                <td><input class="n unitario" name="filas[{{ $i }}][precio_unitario]"></td>
                                <td class="num total-contratado">0.00</td>
                                <td class="num anterior">0</td>
                                <td class="num actual">0</td>
                                <td class="num total-cantidad">0.00</td>
                                <td class="num valor-anterior">0.00</td>
                                <td class="num valor-actual">0.00</td>
                                <td class="num valor-total">0.00</td>
                                <td></td>
                            </tr>
                        @endfor
                        @endif
                        @if ($lineas !== [])
                            <tr class="cierre" data-cierre>
                                <td colspan="5">Trabajos realizados</td>
                                <td class="num pie-contratado">{{ $m($totales['contratado']) }}</td>
                                <td colspan="3"></td>
                                <td class="num pie-anterior">{{ $m($totales['anterior']) }}</td>
                                <td class="num pie-actual">{{ $m($totales['actual']) }}</td>
                                <td class="num pie-acumulado">{{ $m($totales['acumulado']) }}</td>
                                <td></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        <div class="barra">
            @if ($gestiona)
                <button type="button" class="secundario" id="agregar-rubro">Agregar rubro</button>
            @endif
            <button type="submit">Guardar</button>
        </div>
        <template id="fila-nueva">
            @if ($enAplicacion)
                <article class="ficha" data-fila data-nuevo>
                    <label>Descripción<input name="filas[__i__][descripcion]" placeholder="Nuevo rubro"></label>
                    <label>Unidad<input class="u" name="filas[__i__][unidad]" placeholder="u"></label>
                    <h3>Contratado</h3>
                    <div class="pares">
                        <label>Cantidad<input class="n contratada" name="filas[__i__][cantidad_contratada]"></label>
                        <label>Unitario<input class="n unitario" name="filas[__i__][precio_unitario]"></label>
                    </div>
                    <p class="resultado">Total contratado <b class="total-contratado">0.00</b></p>
                    <h3>Cantidades ejecutadas</h3>
                    <div class="pares">
                        <label>Anterior <b class="anterior">0</b></label>
                        <label>Actual <b class="actual">0</b></label>
                    </div>
                    <p class="resultado">Total cantidad <b class="total-cantidad">0.00</b></p>
                    <h3>Total en dólares</h3>
                    <div class="pares">
                        <p class="resultado">Anterior <b class="valor-anterior">0.00</b></p>
                        <p class="resultado">Actual <b class="valor-actual">0.00</b></p>
                    </div>
                    <p class="resultado">Total <b class="valor-total">0.00</b></p>
                </article>
            @else
                <tr data-fila data-nuevo>
                    <td class="num"></td>
                    <td><input name="filas[__i__][descripcion]" placeholder="Nuevo rubro"></td>
                    <td><input class="u" name="filas[__i__][unidad]" placeholder="u"></td>
                    <td><input class="n contratada" name="filas[__i__][cantidad_contratada]"></td>
                    <td><input class="n unitario" name="filas[__i__][precio_unitario]"></td>
                    <td class="num total-contratado">0.00</td>
                    <td class="num anterior">0</td>
                    <td class="num actual">0</td>
                    <td class="num total-cantidad">0.00</td>
                    <td class="num valor-anterior">0.00</td>
                    <td class="num valor-actual">0.00</td>
                    <td class="num valor-total">0.00</td>
                    <td></td>
                </tr>
            @endif
        </template>
    </form>
    @if ($gestiona)
    @foreach ($lineas as $linea)
        <form id="quitar-rubro-{{ $linea['rubro']->id }}" method="post" action="{{ route('rubros.destroy', $linea['rubro']) }}" onsubmit="return confirm('¿Quitar este rubro de la planilla?')">
            @csrf
            @method('delete')
        </form>
    @endforeach
    @endif
    <script>
        const dinero = (valor) => (Math.round((valor + Number.EPSILON) * 100) / 100).toFixed(2);
        const numero = (campo) => {
            if (!campo) return 0;
            const texto = campo instanceof HTMLInputElement ? campo.value : campo.textContent;
            const valor = parseFloat(String(texto).replace(',', '.'));
            return Number.isFinite(valor) ? valor : 0;
        };
        const suma = (selector) => [...document.querySelectorAll(selector)].reduce((total, celda) => total + (parseFloat(celda.textContent) || 0), 0);
        const recalcular = (fila) => {
            const contratada = numero(fila.querySelector('.contratada'));
            const unitario = numero(fila.querySelector('.unitario'));
            const anterior = numero(fila.querySelector('.anterior'));
            const actual = numero(fila.querySelector('.actual'));
            const valorAnterior = anterior * unitario;
            const valorActual = actual * unitario;
            fila.querySelector('.total-contratado').textContent = dinero(contratada * unitario);
            fila.querySelector('.total-cantidad').textContent = dinero(anterior + actual);
            fila.querySelector('.valor-anterior').textContent = dinero(valorAnterior);
            fila.querySelector('.valor-actual').textContent = dinero(valorActual);
            fila.querySelector('.valor-total').textContent = dinero(valorAnterior + valorActual);
            const pie = (selector, destino) => {
                const celda = document.querySelector(destino);
                if (celda) celda.textContent = dinero(suma(selector));
            };
            pie('.total-contratado', '.pie-contratado');
            pie('.valor-anterior', '.pie-anterior');
            pie('.valor-actual', '.pie-actual');
            pie('.valor-total', '.pie-acumulado');
        };
        const lista = document.querySelector('[data-lista]');
        const enlazar = (fila) => {
            if (!fila.querySelector('.contratada')) return;
            fila.querySelectorAll('.contratada, .unitario, .anterior, .actual').forEach((campo) => {
                campo.addEventListener('input', () => recalcular(fila));
            });
        };
        lista.querySelectorAll('[data-fila]').forEach(enlazar);
        const claveCampo = (campo) => ['contratada', 'unitario', 'anterior', 'actual', 'u'].find((clase) => campo.classList.contains(clase))
            || (campo.name.includes('[descripcion]') ? 'descripcion' : campo.name);
        const camposDe = (fila) => [...fila.querySelectorAll('input:not([type="hidden"])')];
        const filasVisibles = () => [...lista.querySelectorAll('[data-fila]')].filter((fila) => !fila.hidden);
        const enfocar = (campo) => {
            if (!campo) return;
            campo.focus();
            campo.select();
            campo.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        };
        const moverCampo = (actual, deltaFila, deltaCampo) => {
            const filas = filasVisibles();
            const fila = actual.closest('[data-fila]');
            const posicion = filas.indexOf(fila);
            if (posicion < 0) return;
            const destinoFila = filas[posicion + deltaFila];
            if (!destinoFila) return;
            const destinos = camposDe(destinoFila);
            if (deltaFila !== 0) {
                enfocar(destinos.find((campo) => claveCampo(campo) === claveCampo(actual)));
                return;
            }
            enfocar(destinos[camposDe(fila).indexOf(actual) + deltaCampo]);
        };
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
            evento.preventDefault();
            if (evento.key === 'ArrowUp') moverCampo(campo, -1, 0);
            if (evento.key === 'ArrowDown') moverCampo(campo, 1, 0);
            if (evento.key === 'ArrowLeft') moverCampo(campo, 0, -1);
            if (evento.key === 'ArrowRight') moverCampo(campo, 0, 1);
        });
        const indices = [...document.querySelectorAll('[name^="filas["]')].map((campo) => {
            const coincidencia = campo.name.match(/filas\[(\d+)\]/);
            return coincidencia ? Number(coincidencia[1]) : -1;
        });
        let indice = Math.max(-1, ...indices) + 1;
        document.getElementById('agregar-rubro')?.addEventListener('click', () => {
            const fila = document.getElementById('fila-nueva').content.cloneNode(true).querySelector('[data-fila]');
            fila.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace('__i__', String(indice));
            });
            indice += 1;
            const cierre = lista.querySelector('[data-cierre]');
            if (cierre) cierre.before(fila);
            else lista.appendChild(fila);
            lista.querySelector('.vacia')?.remove();
            enlazar(fila);
            fila.querySelector('input')?.focus();
        });
        const buscar = document.querySelector('[data-buscar-rubros]');
        const aviso = document.querySelector('[data-sin-rubros]');
        const normalizar = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
        const textoFila = (fila) => {
            const partes = [];
            fila.querySelectorAll('a, strong, .num, .ficha-num').forEach((elemento) => partes.push(elemento.textContent));
            fila.querySelectorAll('input:not([type="hidden"])').forEach((campo) => partes.push(campo.value));
            return normalizar(partes.join(' '));
        };
        const filtrar = () => {
            const consulta = normalizar(buscar.value.trim());
            let visibles = 0;
            let guardados = 0;
            lista.querySelectorAll('[data-fila]').forEach((fila) => {
                if (fila.hasAttribute('data-nuevo')) {
                    fila.hidden = false;
                    return;
                }
                guardados += 1;
                const coincide = consulta === '' || textoFila(fila).includes(consulta);
                fila.hidden = !coincide;
                if (coincide) visibles += 1;
            });
            const cierre = lista.querySelector('[data-cierre]');
            if (cierre) cierre.hidden = consulta !== '';
            if (aviso) aviso.hidden = consulta === '' || visibles > 0 || guardados === 0;
        };
        buscar.addEventListener('input', filtrar);
        buscar.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter') evento.preventDefault();
        });
    </script>
@endsection
