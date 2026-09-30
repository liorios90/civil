@extends('layout')

@section('titulo', $frente->nombre)

@section('contenido')
    <p><a href="{{ route('contratos.show', $frente->contrato) }}">{{ $frente->contrato->codigo_proceso }}</a></p>
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <form method="post" action="{{ route('frentes.update', $frente) }}" class="fila card">
        @csrf
        @method('put')
        <input name="nombre" value="{{ $frente->nombre }}" required>
        <button type="submit">Guardar nombre</button>
        <button class="btn-rojo" type="submit" form="eliminar-frente">Eliminar frente</button>
    </form>
    <form id="eliminar-frente" method="post" action="{{ route('frentes.destroy', $frente) }}" onsubmit="return confirm('¿Eliminar este frente y sus cantidades?')">
        @csrf
        @method('delete')
    </form>
    @php
        $m = fn ($v) => number_format((float) $v, 2);
        $q = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    @endphp
    <form method="post" action="{{ route('rubros.guardar', $frente) }}">
        @csrf
        <div class="card">
            <h2>{{ $frente->nombre }}</h2>
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
                    <tbody>
                        @forelse ($lineas as $i => $linea)
                            @php
                                $rubro = $linea['rubro'];
                                $ejecucion = $linea['ejecucion'];
                                $k = $linea['calculo'];
                                $tieneHoja = $ejecucion && $ejecucion->anexos->contains(fn ($anexo) => $anexo->lineas->isNotEmpty());
                                $bloqueada = $tieneHoja;
                            @endphp
                            <tr>
                                <td class="num">{{ $rubro->numero }}<input type="hidden" name="filas[{{ $i }}][id]" value="{{ $rubro->id }}"></td>
                                <td>
                                    @if ($ejecucion)
                                        <a href="{{ route('anexos.show', $ejecucion) }}">{{ $rubro->descripcion }}</a>
                                    @else
                                        {{ $rubro->descripcion }}
                                    @endif
                                </td>
                                <td><input class="u" name="filas[{{ $i }}][unidad]" value="{{ $rubro->unidad }}"></td>
                                <td><input class="n contratada" name="filas[{{ $i }}][cantidad_contratada]" value="{{ $k['cantidad_contratada'] + 0 }}"></td>
                                <td><input class="n unitario" name="filas[{{ $i }}][precio_unitario]" value="{{ $k['precio_unitario'] + 0 }}"></td>
                                <td class="num total-contratado">{{ $m($k['total_contratado']) }}</td>
                                <td><input class="n anterior" name="filas[{{ $i }}][cantidad_anterior]" value="{{ $q($k['cantidad_anterior']) }}"></td>
                                <td>
                                    @if ($bloqueada)
                                        <input class="n actual" value="{{ $q($k['cantidad_actual']) }}" readonly>
                                    @else
                                        <input class="n actual" name="filas[{{ $i }}][cantidad_actual]" value="{{ $q($k['cantidad_actual']) }}">
                                    @endif
                                </td>
                                <td class="num total-cantidad">{{ $m($k['cantidad_total']) }}</td>
                                <td class="num valor-anterior">{{ $m($k['valor_anterior']) }}</td>
                                <td class="num valor-actual">{{ $m($k['valor_actual']) }}</td>
                                <td class="num valor-total">{{ $m($k['valor_total']) }}</td>
                                <td><button class="danger" type="submit" form="quitar-rubro-{{ $rubro->id }}">Quitar</button></td>
                            </tr>
                        @empty
                            <tr class="vacia"><td colspan="13">Esta planilla no tiene rubros. Agrega uno abajo.</td></tr>
                        @endforelse
                        @for ($n = 0; $n < 3; $n++)
                            @php($i = count($lineas) + $n)
                            <tr>
                                <td class="num"></td>
                                <td><input name="filas[{{ $i }}][descripcion]" placeholder="Nuevo rubro"></td>
                                <td><input class="u" name="filas[{{ $i }}][unidad]" placeholder="u"></td>
                                <td><input class="n contratada" name="filas[{{ $i }}][cantidad_contratada]"></td>
                                <td><input class="n unitario" name="filas[{{ $i }}][precio_unitario]"></td>
                                <td class="num total-contratado">0.00</td>
                                <td><input class="n anterior" name="filas[{{ $i }}][cantidad_anterior]"></td>
                                <td><input class="n actual" name="filas[{{ $i }}][cantidad_actual]"></td>
                                <td class="num total-cantidad">0.00</td>
                                <td class="num valor-anterior">0.00</td>
                                <td class="num valor-actual">0.00</td>
                                <td class="num valor-total">0.00</td>
                                <td></td>
                            </tr>
                        @endfor
                        @if ($lineas !== [])
                            <tr class="cierre">
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
        <div class="barra">
            <button type="button" class="secundario" id="agregar-rubro">Agregar rubro</button>
            <button type="submit">Guardar</button>
        </div>
        <template id="fila-nueva">
            <tr>
                <td class="num"></td>
                <td><input name="filas[__i__][descripcion]" placeholder="Nuevo rubro"></td>
                <td><input class="u" name="filas[__i__][unidad]" placeholder="u"></td>
                <td><input class="n contratada" name="filas[__i__][cantidad_contratada]"></td>
                <td><input class="n unitario" name="filas[__i__][precio_unitario]"></td>
                <td class="num total-contratado">0.00</td>
                <td><input class="n anterior" name="filas[__i__][cantidad_anterior]"></td>
                <td><input class="n actual" name="filas[__i__][cantidad_actual]"></td>
                <td class="num total-cantidad">0.00</td>
                <td class="num valor-anterior">0.00</td>
                <td class="num valor-actual">0.00</td>
                <td class="num valor-total">0.00</td>
                <td></td>
            </tr>
        </template>
    </form>
    @foreach ($lineas as $linea)
        <form id="quitar-rubro-{{ $linea['rubro']->id }}" method="post" action="{{ route('rubros.destroy', $linea['rubro']) }}" onsubmit="return confirm('¿Quitar este rubro de la planilla?')">
            @csrf
            @method('delete')
        </form>
    @endforeach
    <script>
        const dinero = (valor) => (Math.round((valor + Number.EPSILON) * 100) / 100).toFixed(2);
        const numero = (campo) => {
            if (!campo) return 0;
            const valor = parseFloat(String(campo.value).replace(',', '.'));
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
        const tbody = document.querySelector('table.hoja tbody');
        const enlazar = (fila) => {
            if (!fila.querySelector('.contratada')) return;
            fila.querySelectorAll('.contratada, .unitario, .anterior, .actual').forEach((campo) => {
                campo.addEventListener('input', () => recalcular(fila));
            });
        };
        tbody.querySelectorAll('tr').forEach(enlazar);
        const indices = [...document.querySelectorAll('[name^="filas["]')].map((campo) => {
            const coincidencia = campo.name.match(/filas\[(\d+)\]/);
            return coincidencia ? Number(coincidencia[1]) : -1;
        });
        let indice = Math.max(-1, ...indices) + 1;
        document.getElementById('agregar-rubro').addEventListener('click', () => {
            const fila = document.getElementById('fila-nueva').content.cloneNode(true).querySelector('tr');
            fila.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace('__i__', String(indice));
            });
            indice += 1;
            const cierre = tbody.querySelector('tr.cierre');
            if (cierre) cierre.before(fila);
            else tbody.appendChild(fila);
            tbody.querySelector('tr.vacia')?.remove();
            enlazar(fila);
            fila.querySelector('input')?.focus();
        });
    </script>
@endsection
