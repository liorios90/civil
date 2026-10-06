@extends('layout')

@section('titulo', 'Catálogo de rubros')

@section('contenido')
    <p><a class="btn secundario" href="{{ route('inicio') }}">Volver a la pantalla principal</a></p>
    <div class="card">
        <div class="fila catalogo-cabeza">
            <div>
                <h1>Catálogo de rubros</h1>
                <p>Rubros de su empresa. Puede usarlos en un contrato; cambiarlos aquí no modifica los contratos que ya existen.</p>
            </div>
        </div>
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
        <form method="post" action="{{ route('catalogo.update') }}">
            @csrf
            @method('put')
            <p class="buscar"><input type="search" data-buscar-rubros placeholder="Buscar rubro por número o descripción" autocomplete="off"></p>
            <p data-sin-rubros hidden>Ningún rubro coincide.</p>
            <div class="scroll">
                <table class="hoja catalogo">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Descripción del rubro</th>
                            <th>U</th>
                            <th>Cantidad</th>
                            <th>P. unitario</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rubros as $i => $rubro)
                            @php($datosMedicion = $rubro->medicion ?? [])
                            <tr data-fila>
                                <td class="num">{{ $rubro->numero }}<input type="hidden" name="filas[{{ $i }}][numero]" value="{{ $rubro->numero }}"></td>
                                <td><input name="filas[{{ $i }}][descripcion]" value="{{ $rubro->descripcion }}"></td>
                                <td><input class="u" name="filas[{{ $i }}][unidad]" value="{{ $rubro->unidad }}"></td>
                                <td><input class="n cant" name="filas[{{ $i }}][cantidad_contratada]" value="{{ $rubro->cantidad_contratada + 0 }}"></td>
                                <td><input class="n precio" name="filas[{{ $i }}][precio_unitario]" value="{{ $rubro->precio_unitario + 0 }}"></td>
                                <td class="calc total"></td>
                                <td class="ops">
                                    <button type="button" class="texto" data-medicion>Medición
                                        @if (count($datosMedicion))
                                            <span class="cuenta">{{ count($datosMedicion) }}</span>
                                        @endif
                                    </button>
                                    <button class="danger" type="button" data-quitar>Quitar</button>
                                </td>
                            </tr>
                            <tr data-config class="cerrada">
                                <td colspan="7">
                                    @include('catalogo.medicion', ['indice' => $i, 'medicion' => $datosMedicion])
                                </td>
                            </tr>
                        @endforeach
                        @for ($n = 0; $n < 3; $n++)
                            @php($i = $rubros->count() + $n)
                            <tr data-fila data-nuevo>
                                <td class="num"></td>
                                <td><input name="filas[{{ $i }}][descripcion]" placeholder="Nuevo rubro"></td>
                                <td><input class="u" name="filas[{{ $i }}][unidad]" placeholder="u"></td>
                                <td><input class="n cant" name="filas[{{ $i }}][cantidad_contratada]"></td>
                                <td><input class="n precio" name="filas[{{ $i }}][precio_unitario]"></td>
                                <td class="calc total"></td>
                                <td class="ops">
                                    <button type="button" class="texto" data-medicion>Medición</button>
                                    <button class="danger" type="button" data-quitar>Quitar</button>
                                </td>
                            </tr>
                            <tr data-config class="cerrada">
                                <td colspan="7">
                                    @include('catalogo.medicion', ['indice' => $i, 'medicion' => []])
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <template id="fila-nueva">
                <tr data-fila data-nuevo>
                    <td class="num"></td>
                    <td><input name="filas[__i__][descripcion]" placeholder="Nuevo rubro"></td>
                    <td><input class="u" name="filas[__i__][unidad]" placeholder="u"></td>
                    <td><input class="n cant" name="filas[__i__][cantidad_contratada]"></td>
                    <td><input class="n precio" name="filas[__i__][precio_unitario]"></td>
                    <td class="calc total"></td>
                    <td class="ops">
                        <button type="button" class="texto" data-medicion>Medición</button>
                        <button class="danger" type="button" data-quitar>Quitar</button>
                    </td>
                </tr>
                <tr data-config class="cerrada">
                    <td colspan="7">
                        @include('catalogo.medicion', ['indice' => '__i__', 'medicion' => []])
                    </td>
                </tr>
            </template>
            <template id="dato-nuevo">
                <div class="dato" data-dato>
                    <input name="filas[__i__][medicion][__j__][etiqueta]" placeholder="Nombre del dato" maxlength="40">
                    <input name="filas[__i__][medicion][__j__][formula]" placeholder="Fórmula, ej. =altura*peso" maxlength="200">
                    <button class="danger" type="button" data-quitar-dato>Quitar</button>
                </div>
            </template>
            <div class="barra catalogo-barra">
                <button type="button" class="secundario" id="agregar-rubro">Agregar rubro</button>
                <button type="submit">Guardar catálogo</button>
            </div>
        </form>
    </div>
    <details class="card catalogo-excel">
        <summary>Traer rubros desde Excel</summary>
        <form method="post" action="{{ route('catalogo.excel') }}" enctype="multipart/form-data" onsubmit="return confirm('El Excel reemplaza el catálogo general. Los contratos que ya existen no cambian.')">
            @csrf
            <p>Columnas: Descripción, Unidad y Precio unitario. También puede incluir Cantidad y Número.</p>
            <p><input type="file" name="rubros_excel" accept=".xlsx,.xls" required></p>
            <p><button type="submit">Cargar Excel</button></p>
        </form>
    </details>
    <style>
        .catalogo-cabeza { align-items: flex-end; margin-bottom: 8px; }
        .catalogo-cabeza p { margin: 0; color: #4a5b6d; }
        table.catalogo td.ops { padding: 4px 8px; white-space: nowrap; }
        table.catalogo button.texto { background: transparent; color: #0f3d68; padding: 4px 8px; }
        table.catalogo button.texto .cuenta { display: inline-block; min-width: 16px; margin-left: 4px; padding: 0 5px; border-radius: 999px; background: #0f3d68; color: #fff; font-size: 11px; line-height: 16px; }
        tr[data-config].cerrada { display: none; }
        tr[data-config] > td { background: #f7fafc; }
        .medicion { margin: 8px; padding: 12px 14px; background: #fff; border: 1px solid #e1e8ef; border-radius: 10px; }
        .medicion-cabeza { margin: 0 0 10px; }
        .medicion-cabeza span { display: block; margin-top: 2px; color: #5c6d7e; font-size: 13px; }
        .medicion-accion { margin: 4px 0 0; }
        .dato { display: grid; grid-template-columns: minmax(140px, 1fr) minmax(180px, 1.4fr) auto; gap: 8px; align-items: center; margin-bottom: 8px; }
        table.catalogo .dato input { border: 1px solid #c5d0db; border-radius: 6px; background: #fff; min-width: 0; }
        .catalogo-barra { display: flex; gap: 8px; }
        .catalogo-excel summary { cursor: pointer; font-weight: 700; }
        .catalogo-excel form { margin-top: 12px; }
        @media (max-width: 700px) { .dato { grid-template-columns: 1fr; } }
    </style>
    <script>
        const tbody = document.querySelector('table.hoja tbody');
        const enlazar = (fila) => {
            const cant = fila.querySelector('.cant');
            const precio = fila.querySelector('.precio');
            const total = fila.querySelector('.total');
            if (!cant || !precio || !total) return;
            const pintar = () => {
                const valor = (parseFloat(cant.value) || 0) * (parseFloat(precio.value) || 0);
                total.textContent = valor ? valor.toFixed(2) : '';
            };
            cant.addEventListener('input', pintar);
            precio.addEventListener('input', pintar);
            pintar();
        };
        tbody.querySelectorAll('tr').forEach(enlazar);
        const actualizarCuenta = (config) => {
            const boton = config?.previousElementSibling?.querySelector('[data-medicion]');
            if (!boton) return;
            const total = config.querySelectorAll('[data-dato]').length;
            let cuenta = boton.querySelector('.cuenta');
            if (!total) {
                cuenta?.remove();
                return;
            }
            if (!cuenta) {
                cuenta = document.createElement('span');
                cuenta.className = 'cuenta';
                boton.append(document.createTextNode(' '), cuenta);
            }
            cuenta.textContent = String(total);
        };
        tbody.addEventListener('click', (evento) => {
            const ver = evento.target.closest('[data-medicion]');
            if (ver) {
                const config = ver.closest('tr')?.nextElementSibling;
                if (config?.hasAttribute('data-config')) config.classList.toggle('cerrada');
                return;
            }
            const quitarDato = evento.target.closest('[data-quitar-dato]');
            if (quitarDato) {
                const config = quitarDato.closest('tr');
                quitarDato.closest('[data-dato]')?.remove();
                actualizarCuenta(config);
                return;
            }
            const boton = evento.target.closest('[data-quitar]');
            if (!boton) return;
            const fila = boton.closest('tr');
            if (fila?.nextElementSibling?.hasAttribute('data-config')) fila.nextElementSibling.remove();
            fila?.remove();
        });
        tbody.addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-agregar-dato]');
            if (!boton) return;
            const config = boton.closest('tr');
            const fila = config?.previousElementSibling;
            const nombre = fila?.querySelector('[name$="[descripcion]"]')?.name || '';
            const coincidencia = nombre.match(/filas\[(\d+|__i__)\]/);
            const i = coincidencia ? coincidencia[1] : '0';
            const lista = boton.closest('[data-config-panel]')?.querySelector('[data-datos]');
            if (!lista) return;
            const j = lista.querySelectorAll('[data-dato]').length;
            const dato = document.getElementById('dato-nuevo').content.cloneNode(true).querySelector('[data-dato]');
            dato.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace('__i__', i).replace('__j__', String(j));
            });
            lista.appendChild(dato);
            actualizarCuenta(config);
            dato.querySelector('input')?.focus();
        });
        const indices = [...document.querySelectorAll('[name^="filas["]')].map((campo) => {
            const coincidencia = campo.name.match(/filas\[(\d+)\]/);
            return coincidencia ? Number(coincidencia[1]) : -1;
        });
        let indice = Math.max(-1, ...indices) + 1;
        document.getElementById('agregar-rubro').addEventListener('click', () => {
            const contenido = document.getElementById('fila-nueva').content.cloneNode(true);
            const i = String(indice);
            indice += 1;
            contenido.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replaceAll('__i__', i);
            });
            const fila = contenido.querySelector('[data-fila]');
            tbody.appendChild(contenido);
            enlazar(fila);
            fila.querySelector('input')?.focus();
        });
        const buscar = document.querySelector('[data-buscar-rubros]');
        const aviso = document.querySelector('[data-sin-rubros]');
        const normalizar = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
        const textoFila = (fila) => {
            const partes = [fila.querySelector('.num')?.textContent || ''];
            fila.querySelectorAll('input:not([type="hidden"])').forEach((campo) => partes.push(campo.value));
            return normalizar(partes.join(' '));
        };
        buscar.addEventListener('input', () => {
            const consulta = normalizar(buscar.value.trim());
            let visibles = 0;
            let guardados = 0;
            tbody.querySelectorAll('[data-fila]').forEach((fila) => {
                const config = fila.nextElementSibling?.hasAttribute('data-config') ? fila.nextElementSibling : null;
                if (fila.hasAttribute('data-nuevo')) {
                    fila.hidden = false;
                    if (config) config.hidden = false;
                    return;
                }
                guardados += 1;
                const coincide = consulta === '' || textoFila(fila).includes(consulta);
                fila.hidden = !coincide;
                if (config) config.hidden = !coincide;
                if (coincide) visibles += 1;
            });
            aviso.hidden = consulta === '' || visibles > 0 || guardados === 0;
        });
        buscar.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter') evento.preventDefault();
        });
    </script>
@endsection
