@extends('layout')

@section('titulo', $contrato->exists ? $contrato->codigo_proceso : 'Nuevo contrato')

@section('contenido')
    <div class="card">
        <p><a class="btn secundario" href="{{ route('inicio') }}">Volver a la pantalla principal</a></p>
        <h1>{{ $contrato->exists ? 'Datos del contrato' : 'Nuevo contrato' }}</h1>
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
        <form method="post" action="{{ $contrato->exists ? route('contratos.update', $contrato) : route('contratos.store') }}" enctype="multipart/form-data">
            @csrf
            @if ($contrato->exists) @method('put') @endif
            <div class="grid">
                <label>Entidad<input name="entidad" value="{{ old('entidad', $contrato->entidad) }}"></label>
                <label>Código del proceso<input name="codigo_proceso" value="{{ old('codigo_proceso', $contrato->codigo_proceso) }}" required></label>
                <label class="ancho">Objeto<textarea name="objeto">{{ old('objeto', $contrato->objeto) }}</textarea></label>
                <label>Contratista<input name="contratista" value="{{ old('contratista', $contrato->contratista) }}" required></label>
                <label>Fiscalizador<input name="fiscalizador" value="{{ old('fiscalizador', $contrato->fiscalizador) }}"></label>
                <label>Administrador<input name="administrador" value="{{ old('administrador', $contrato->administrador) }}"></label>
                <label>Ubicación<input name="ubicacion" value="{{ old('ubicacion', $contrato->ubicacion) }}"></label>
                <label>Provincia<input name="provincia" value="{{ old('provincia', $contrato->provincia) }}"></label>
                <label>Plazo<input name="plazo" value="{{ old('plazo', $contrato->plazo) }}"></label>
                <label>Inicio<input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', optional($contrato->fecha_inicio)->format('Y-m-d')) }}"></label>
                <label>Término<input type="date" name="fecha_termino" value="{{ old('fecha_termino', optional($contrato->fecha_termino)->format('Y-m-d')) }}"></label>
                <label>Monto sin IVA<input type="number" step="0.01" name="monto_contrato" value="{{ old('monto_contrato', $contrato->monto_contrato ?? 0) }}"></label>
                <label>IVA (%)<input type="number" step="0.01" min="0" max="100" name="iva_porcentaje" value="{{ old('iva_porcentaje', $contrato->iva_porcentaje ?? 15) }}" required><small style="color:#4a5b6d;margin-top:2px">Se usa al imprimir la planilla. Por defecto 15.</small></label>
                <label>Anticipo (0.50 = 50 %)<input type="number" step="0.01" name="porcentaje_anticipo" value="{{ old('porcentaje_anticipo', $contrato->porcentaje_anticipo ?? 0) }}"></label>
            </div>
            @if ($editarRubros)
                @php
                    $filasViejas = old('filas');
                    if (is_array($filasViejas)) {
                        $filasRubro = collect($filasViejas)
                            ->filter(fn ($fila) => trim((string) ($fila['descripcion'] ?? '')) !== '')
                            ->values();
                    } else {
                        $filasRubro = $rubros->map(fn ($rubro) => [
                            'id' => $rubro->id,
                            'numero' => $rubro->numero,
                            'descripcion' => $rubro->descripcion,
                            'unidad' => $rubro->unidad,
                            'cantidad_contratada' => $rubro->cantidad_contratada + 0,
                            'precio_unitario' => $rubro->precio_unitario + 0,
                            'medicion' => $rubro->medicion ?? [],
                        ])->values();
                    }
                @endphp
                <h2>Rubros del contrato</h2>
                @if ($contrato->exists && ($catalogoRubros = $contrato->catalogo()->first()))
                    @include('historial.resumen', [
                        'creado' => null,
                        'filtro' => ['frente_id' => $catalogoRubros->id],
                        'enlace' => route('historial.index', [$contrato, 'frente' => $catalogoRubros->id]),
                    ])
                @endif
                <p>Estos rubros son solo de este contrato. Ponga <strong>cantidad mayor a 0</strong> para que aparezcan en la última planilla y se pueda entrar a mediciones. Al guardar o subir Excel se actualiza la última planilla; las planillas anteriores no se tocan.</p>
                <p>
                    @if ($catalogoGeneral->isNotEmpty())
                        <button type="button" class="secundario" id="usar-catalogo">Usar catálogo general ({{ $catalogoGeneral->count() }})</button>
                    @endif
                    <a href="{{ route('catalogo.edit') }}">Catálogo general</a>
                </p>
                @if ($catalogoGeneral->isNotEmpty())
                    @php
                        $catalogoJson = $catalogoGeneral->map(function ($rubro) {
                            return [
                                'descripcion' => $rubro->descripcion,
                                'unidad' => $rubro->unidad,
                                'cantidad_contratada' => $rubro->cantidad_contratada + 0,
                                'precio_unitario' => $rubro->precio_unitario + 0,
                                'medicion' => $rubro->medicion ?? [],
                            ];
                        })->values();
                    @endphp
                    <script type="application/json" id="catalogo-general">@json($catalogoJson)</script>
                @endif
                @unless ($contrato->exists)
                    <p><label>Subir desde Excel<input type="file" name="rubros_excel" accept=".xlsx,.xls"></label></p>
                    <p>La primera fila puede decir Descripción, Unidad, Cantidad y Precio unitario. Si no trae Cantidad, se asume 1.</p>
                @endunless
                <p class="buscar"><input type="search" data-buscar-rubros placeholder="Buscar rubro por número o descripción" autocomplete="off"></p>
                <p data-sin-rubros hidden>Ningún rubro coincide.</p>
                <div class="scroll">
                    <table class="hoja rubros-contrato">
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
                            @foreach ($filasRubro as $i => $rubro)
                                @php($datosMedicion = $rubro['medicion'] ?? [])
                                <tr data-fila>
                                    <td class="num">{{ $rubro['numero'] ?? '' }}@if (! empty($rubro['numero']))<input type="hidden" name="filas[{{ $i }}][numero]" value="{{ $rubro['numero'] }}">@endif</td>
                                    <td><input name="filas[{{ $i }}][descripcion]" value="{{ $rubro['descripcion'] }}"></td>
                                    <td><input class="u" name="filas[{{ $i }}][unidad]" value="{{ $rubro['unidad'] }}"></td>
                                    <td><input class="n cant" name="filas[{{ $i }}][cantidad_contratada]" value="{{ $rubro['cantidad_contratada'] }}"></td>
                                    <td><input class="n precio" name="filas[{{ $i }}][precio_unitario]" value="{{ $rubro['precio_unitario'] }}"></td>
                                    <td class="calc total"></td>
                                    <td class="ops">
                                        <button type="button" class="texto" data-medicion>Fórmulas
                                            @if (count($datosMedicion))
                                                <span class="cuenta">{{ count($datosMedicion) }}</span>
                                            @endif
                                        </button>
                                        @if ($contrato->exists && ! empty($rubro['id']))
                                            <button class="danger" type="submit" form="eliminar-rubro-{{ $rubro['id'] }}">Eliminar</button>
                                        @else
                                            <button class="danger" type="button" data-quitar>Eliminar</button>
                                        @endif
                                    </td>
                                </tr>
                                <tr data-config class="cerrada">
                                    <td colspan="7">
                                        @include('catalogo.medicion', ['indice' => $i, 'medicion' => $datosMedicion])
                                    </td>
                                </tr>
                            @endforeach
                            @for ($n = 0; $n < 3; $n++)
                                @php($i = $filasRubro->count() + $n)
                                <tr data-fila data-nuevo>
                                    <td class="num"></td>
                                    <td><input name="filas[{{ $i }}][descripcion]" placeholder="Nuevo rubro"></td>
                                    <td><input class="u" name="filas[{{ $i }}][unidad]" placeholder="u"></td>
                                    <td><input class="n cant" name="filas[{{ $i }}][cantidad_contratada]"></td>
                                    <td><input class="n precio" name="filas[{{ $i }}][precio_unitario]"></td>
                                    <td class="calc total"></td>
                                    <td class="ops">
                                        <button type="button" class="texto" data-medicion>Fórmulas</button>
                                        <button class="danger" type="button" data-quitar>Eliminar</button>
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
                <p><button type="button" class="secundario" id="agregar-rubro">Agregar rubro</button></p>
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
                            <button class="danger" type="button" data-quitar>Eliminar</button>
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
                <div class="modal-fondo" id="modal-formulas-rubro" hidden>
                    <div class="modal-formulas-rubro" role="dialog" aria-modal="true" aria-labelledby="titulo-formulas-rubro">
                        <div class="modal-cabeza">
                            <div>
                                <h2 id="titulo-formulas-rubro">Fórmulas del rubro</h2>
                                <p data-rubro-nombre>Configure los datos y fórmulas de medición.</p>
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
                    table.rubros-contrato td.ops { white-space: nowrap; }
                    table.rubros-contrato button.texto { background: transparent; color: #0f3d68; padding: 4px 8px; }
                    table.rubros-contrato button.texto .cuenta { display: inline-block; min-width: 16px; margin-left: 4px; padding: 0 5px; border-radius: 999px; background: #0f3d68; color: #fff; font-size: 11px; line-height: 16px; }
                    tr[data-config] { display: none !important; }
                    .medicion { margin: 0; padding: 0; background: transparent; border: 0; }
                    .medicion-cabeza { margin: 0 0 12px; }
                    .medicion-cabeza span { display: block; margin-top: 2px; color: #5c6d7e; font-size: 13px; }
                    .dato { display: grid; grid-template-columns: minmax(140px, 1fr) minmax(180px, 1.4fr) auto; gap: 8px; align-items: center; margin-bottom: 8px; }
                    .dato input { border: 1px solid #c5d0db; border-radius: 6px; background: #fff; min-width: 0; }
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
            @endif
            <p class="acciones"><button type="submit">Guardar contrato</button> <a class="btn secundario" href="{{ route('inicio') }}">Volver a la pantalla principal</a></p>
        </form>
        @if ($contrato->exists && $editarRubros)
            @foreach ($filasRubro as $rubro)
                @if (! empty($rubro['id']))
                    <form id="eliminar-rubro-{{ $rubro['id'] }}" method="post" action="{{ route('contratos.rubros.eliminar', [$contrato, $rubro['id']]) }}" onsubmit="return confirm('¿Eliminar este rubro del contrato?')">
                        @csrf
                        @method('delete')
                    </form>
                @endif
            @endforeach
        @endif
        @if ($contrato->exists && $editarRubros)
            <form method="post" action="{{ route('contratos.rubros.excel', $contrato) }}" enctype="multipart/form-data" class="card">
                @csrf
                <h2>Subir rubros desde Excel</h2>
                <p>Columnas: Descripción, Unidad, Cantidad y Precio unitario. Reemplaza los rubros de este contrato y los pasa a la última planilla (con cantidad &gt; 0) para mediciones. Si no trae Cantidad, se asume 1.</p>
                <p><input type="file" name="rubros_excel" accept=".xlsx,.xls" required></p>
                <p><button type="submit">Cargar Excel</button></p>
            </form>
        @endif
        @if ($editarRubros)
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
                            : 'Configure los datos y fórmulas de medición.';
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
                const agregarFila = () => {
                    const contenido = document.getElementById('fila-nueva').content.cloneNode(true);
                    const i = String(indice);
                    indice += 1;
                    contenido.querySelectorAll('[name]').forEach((campo) => {
                        campo.name = campo.name.replaceAll('__i__', i);
                    });
                    const fila = contenido.querySelector('[data-fila]');
                    const ancla = tbody.querySelector('[data-nuevo]');
                    if (ancla) tbody.insertBefore(contenido, ancla);
                    else tbody.appendChild(contenido);
                    enlazar(fila);
                    return fila;
                };
                document.getElementById('agregar-rubro').addEventListener('click', () => {
                    agregarFila().querySelector('input')?.focus();
                });
                const botonCatalogo = document.getElementById('usar-catalogo');
                const datosCatalogo = document.getElementById('catalogo-general');
                if (botonCatalogo && datosCatalogo) {
                    botonCatalogo.addEventListener('click', () => {
                        const normalizarTexto = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es').trim();
                        const existentes = new Set();
                        tbody.querySelectorAll('[name$="[descripcion]"]').forEach((campo) => {
                            const texto = normalizarTexto(campo.value);
                            if (texto) existentes.add(texto);
                        });
                        let agregados = 0;
                        JSON.parse(datosCatalogo.textContent).forEach((rubro) => {
                            const texto = normalizarTexto(rubro.descripcion || '');
                            if (!texto || existentes.has(texto)) return;
                            existentes.add(texto);
                            const fila = agregarFila();
                            const config = fila.nextElementSibling;
                            fila.removeAttribute('data-nuevo');
                            fila.querySelector('[name$="[descripcion]"]').value = rubro.descripcion;
                            fila.querySelector('[name$="[unidad]"]').value = rubro.unidad || '';
                            fila.querySelector('[name$="[cantidad_contratada]"]').value = rubro.cantidad_contratada ?? '';
                            fila.querySelector('[name$="[precio_unitario]"]').value = rubro.precio_unitario ?? '';
                            const lista = config?.querySelector('[data-datos]');
                            if (lista) {
                                lista.innerHTML = '';
                                (rubro.medicion || []).forEach((dato, j) => {
                                    const bloque = document.getElementById('dato-nuevo').content.cloneNode(true).querySelector('[data-dato]');
                                    const i = fila.querySelector('[name$="[descripcion]"]').name.match(/filas\[(\d+)\]/)?.[1] || '0';
                                    bloque.querySelectorAll('[name]').forEach((campo) => {
                                        campo.name = campo.name.replace('__i__', i).replace('__j__', String(j));
                                    });
                                    bloque.querySelector('[name$="[etiqueta]"]').value = dato.etiqueta || '';
                                    bloque.querySelector('[name$="[formula]"]').value = dato.formula || '';
                                    lista.appendChild(bloque);
                                });
                                actualizarCuenta(config);
                            }
                            fila.querySelector('.cant')?.dispatchEvent(new Event('input'));
                            agregados += 1;
                        });
                        botonCatalogo.textContent = agregados > 0 ? 'Se agregaron ' + agregados + ' rubros' : 'Esos rubros ya estaban en el contrato';
                    });
                }
                const buscar = document.querySelector('[data-buscar-rubros]');
                const aviso = document.querySelector('[data-sin-rubros]');
                const normalizar = (texto) => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
                const textoFila = (fila) => {
                    const partes = [fila.querySelector('.num')?.textContent || ''];
                    fila.querySelectorAll('input:not([type="hidden"])').forEach((campo) => partes.push(campo.value));
                    return normalizar(partes.join(' '));
                };
                const filtrar = () => {
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
                    if (aviso) aviso.hidden = consulta === '' || visibles > 0 || guardados === 0;
                };
                buscar.addEventListener('input', filtrar);
                buscar.addEventListener('keydown', (evento) => {
                    if (evento.key === 'Enter') evento.preventDefault();
                });
                const claveCampo = (campo) => ['u', 'cant', 'precio'].find((clase) => campo.classList.contains(clase))
                    || (campo.name.includes('[descripcion]') ? 'descripcion' : campo.name);
                const camposDe = (fila) => [...fila.querySelectorAll('input:not([type="hidden"])')];
                const filasVisibles = () => [...tbody.querySelectorAll('[data-fila]')].filter((fila) => !fila.hidden);
                const enfocar = (campo) => {
                    if (!campo) return;
                    campo.focus();
                    campo.select();
                    campo.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                };
                tbody.addEventListener('keydown', (evento) => {
                    const campo = evento.target;
                    if (!(campo instanceof HTMLInputElement) || campo.type === 'hidden' || evento.altKey || evento.ctrlKey || evento.metaKey) return;
                    if (campo.closest('[data-config]')) return;
                    const horizontal = evento.key === 'ArrowLeft' || evento.key === 'ArrowRight';
                    const vertical = evento.key === 'ArrowUp' || evento.key === 'ArrowDown';
                    if (!horizontal && !vertical) return;
                    if (horizontal && campo.selectionStart != null) {
                        const completo = campo.selectionStart === 0 && campo.selectionEnd === campo.value.length;
                        const izquierda = evento.key === 'ArrowLeft' && campo.selectionStart === 0 && campo.selectionEnd === 0;
                        const derecha = evento.key === 'ArrowRight' && campo.selectionStart === campo.value.length;
                        if (!completo && !izquierda && !derecha) return;
                    }
                    const fila = campo.closest('[data-fila]');
                    const filas = filasVisibles();
                    const posicion = filas.indexOf(fila);
                    if (!fila || posicion < 0) return;
                    evento.preventDefault();
                    const deltaFila = evento.key === 'ArrowUp' ? -1 : (evento.key === 'ArrowDown' ? 1 : 0);
                    const deltaCampo = evento.key === 'ArrowLeft' ? -1 : (evento.key === 'ArrowRight' ? 1 : 0);
                    const destinoFila = filas[posicion + deltaFila];
                    if (!destinoFila) return;
                    const destinos = camposDe(destinoFila);
                    if (deltaFila !== 0) {
                        enfocar(destinos.find((destino) => claveCampo(destino) === claveCampo(campo)));
                        return;
                    }
                    enfocar(destinos[camposDe(fila).indexOf(campo) + deltaCampo]);
                });
            </script>
        @endif
    </div>
@endsection
