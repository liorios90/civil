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
                                    <button type="button" class="texto" data-medicion>Fórmulas
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
                                    <button type="button" class="texto" data-medicion>Fórmulas</button>
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
                        <button type="button" class="texto" data-medicion>Fórmulas</button>
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
    <div class="modal-fondo" id="modal-formulas-rubro" hidden>
        <div class="modal-formulas-rubro" role="dialog" aria-modal="true" aria-labelledby="titulo-formulas-rubro">
            <div class="modal-cabeza">
                <div>
                    <h2 id="titulo-formulas-rubro">Fórmulas del rubro</h2>
                    <p data-rubro-nombre>Opcional: agregue datos y fórmulas. Si lo deja vacío, en mediciones solo habrá Descripción y Cantidad.</p>
                </div>
                <button type="button" class="cerrar-modal" data-cerrar-formulas-rubro title="Cerrar">×</button>
            </div>
            <div class="modal-cuerpo" data-formulas-destino></div>
            <div class="modal-pie">
                <button type="button" class="secundario" data-cerrar-formulas-rubro>Listo</button>
            </div>
        </div>
    </div>
    <style>
        .catalogo-cabeza { align-items: flex-end; margin-bottom: 8px; }
        .catalogo-cabeza p { margin: 0; color: #4a5b6d; }
        table.catalogo td.ops { padding: 4px 8px; white-space: nowrap; }
        table.catalogo button.texto { background: transparent; color: #0f3d68; padding: 4px 8px; }
        table.catalogo button.texto .cuenta { display: inline-block; min-width: 16px; margin-left: 4px; padding: 0 5px; border-radius: 999px; background: #0f3d68; color: #fff; font-size: 11px; line-height: 16px; }
        tr[data-config] { display: none !important; }
        .medicion { margin: 0; padding: 0; background: transparent; border: 0; }
        .medicion-cabeza { margin: 0 0 12px; }
        .medicion-cabeza span { display: block; margin-top: 2px; color: #5c6d7e; font-size: 13px; }
        .medicion-accion { margin: 4px 0 0; }
        .dato { display: grid; grid-template-columns: minmax(140px, 1fr) minmax(180px, 1.4fr) auto; gap: 8px; align-items: center; margin-bottom: 8px; }
        table.catalogo .dato input, .dato input { border: 1px solid #c5d0db; border-radius: 6px; background: #fff; min-width: 0; }
        .catalogo-barra { display: flex; gap: 8px; }
        .catalogo-excel summary { cursor: pointer; font-weight: 700; }
        .catalogo-excel form { margin-top: 12px; }
        .modal-fondo { position: fixed; inset: 0; z-index: 80; background: rgba(15, 33, 51, .48); display: flex; align-items: center; justify-content: center; padding: 16px; }
        .modal-fondo[hidden] { display: none; }
        .modal-formulas-rubro { width: min(720px, 100%); max-height: min(88vh, 820px); overflow: auto; background: #fff; border-radius: 14px; box-shadow: 0 18px 50px rgba(15, 33, 51, .28); border: 1px solid #d5dde5; }
        .modal-formulas-rubro .modal-cabeza { padding: 18px 20px 12px; border-bottom: 1px solid #e7eef5; display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; background: linear-gradient(180deg, #f7fafc 0%, #fff 100%); }
        .modal-formulas-rubro .modal-cabeza h2 { margin: 0 0 4px; font-size: 18px; color: #0f3d68; }
        .modal-formulas-rubro .modal-cabeza p { margin: 0; color: #4a5b6d; font-size: 13px; }
        .modal-formulas-rubro .cerrar-modal { background: transparent; color: #4a5b6d; font-size: 22px; line-height: 1; padding: 4px 8px; min-width: 0; }
        .modal-formulas-rubro .modal-cuerpo { padding: 16px 20px; }
        .modal-formulas-rubro .modal-pie { padding: 12px 20px 18px; border-top: 1px solid #e7eef5; display: flex; justify-content: flex-end; background: #fafbfc; }
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
        tbody.querySelectorAll('[data-fila]').forEach(enlazar);
        const modalFormulas = document.getElementById('modal-formulas-rubro');
        const destinoFormulas = modalFormulas?.querySelector('[data-formulas-destino]');
        const nombreRubroModal = modalFormulas?.querySelector('[data-rubro-nombre]');
        let configActiva = null;
        const panelActivo = () => destinoFormulas?.querySelector('[data-config-panel]')
            || configActiva?.querySelector('[data-config-panel]');
        const actualizarCuenta = (config) => {
            const boton = config?.previousElementSibling?.querySelector('[data-medicion]');
            if (!boton) return;
            const total = (panelActivo() || config)?.querySelectorAll('[data-dato]').length || 0;
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
        const devolverFormulas = () => {
            if (!configActiva || !destinoFormulas) return;
            const panel = destinoFormulas.querySelector('[data-config-panel]');
            if (panel) configActiva.querySelector('td')?.appendChild(panel);
            configActiva = null;
        };
        const cerrarFormulas = () => {
            devolverFormulas();
            if (modalFormulas) modalFormulas.hidden = true;
        };
        const abrirFormulas = (boton) => {
            const fila = boton.closest('tr');
            const config = fila?.nextElementSibling;
            const panel = config?.querySelector('[data-config-panel]');
            if (!config || !panel || !modalFormulas || !destinoFormulas) return;
            devolverFormulas();
            configActiva = config;
            const nombre = fila.querySelector('[name$="[descripcion]"]')?.value?.trim();
            if (nombreRubroModal) {
                nombreRubroModal.textContent = nombre
                    ? 'Rubro: ' + nombre
                    : 'Opcional: agregue datos y fórmulas. Si lo deja vacío, en mediciones solo habrá Descripción y Cantidad.';
            }
            destinoFormulas.appendChild(panel);
            modalFormulas.hidden = false;
            destinoFormulas.querySelector('input')?.focus();
        };
        const agregarDatoFormula = (boton) => {
            const panel = boton.closest('[data-config-panel]') || panelActivo();
            const config = configActiva || boton.closest('tr[data-config]');
            const fila = config?.previousElementSibling;
            const nombre = fila?.querySelector('[name$="[descripcion]"]')?.name || '';
            const coincidencia = nombre.match(/filas\[(\d+|__i__)\]/);
            const i = coincidencia ? coincidencia[1] : '0';
            const lista = panel?.querySelector('[data-datos]');
            if (!lista) return;
            const j = lista.querySelectorAll('[data-dato]').length;
            const dato = document.getElementById('dato-nuevo').content.cloneNode(true).querySelector('[data-dato]');
            dato.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace('__i__', i).replace('__j__', String(j));
            });
            lista.appendChild(dato);
            actualizarCuenta(config);
            dato.querySelector('input')?.focus();
        };
        const quitarDatoFormula = (boton) => {
            const config = configActiva || boton.closest('tr[data-config]');
            boton.closest('[data-dato]')?.remove();
            actualizarCuenta(config);
        };
        document.querySelectorAll('[data-cerrar-formulas-rubro]').forEach((boton) => {
            boton.addEventListener('click', cerrarFormulas);
        });
        modalFormulas?.addEventListener('click', (evento) => {
            if (evento.target === modalFormulas) {
                cerrarFormulas();
                return;
            }
            const quitarDato = evento.target.closest('[data-quitar-dato]');
            if (quitarDato) {
                quitarDatoFormula(quitarDato);
                return;
            }
            const agregarDato = evento.target.closest('[data-agregar-dato]');
            if (agregarDato) agregarDatoFormula(agregarDato);
        });
        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape' && modalFormulas && !modalFormulas.hidden) cerrarFormulas();
        });
        tbody.addEventListener('click', (evento) => {
            const ver = evento.target.closest('[data-medicion]');
            if (ver) {
                abrirFormulas(ver);
                return;
            }
            const boton = evento.target.closest('[data-quitar]');
            if (!boton) return;
            const fila = boton.closest('tr');
            if (fila?.nextElementSibling?.hasAttribute('data-config')) fila.nextElementSibling.remove();
            fila?.remove();
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
