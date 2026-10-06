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
                <p>Estos rubros son solo de este contrato. Se copian al crear una planilla. Eliminar quita el rubro de este contrato; las planillas que ya existen no cambian.</p>
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
                            ];
                        })->values();
                    @endphp
                    <script type="application/json" id="catalogo-general">@json($catalogoJson)</script>
                @endif
                @unless ($contrato->exists)
                    <p><label>Subir desde Excel<input type="file" name="rubros_excel" accept=".xlsx,.xls"></label></p>
                    <p>La primera fila puede decir Descripción, Unidad y Precio unitario.</p>
                @endunless
                <p class="buscar"><input type="search" data-buscar-rubros placeholder="Buscar rubro por número o descripción" autocomplete="off"></p>
                <p data-sin-rubros hidden>Ningún rubro coincide.</p>
                <div class="scroll">
                    <table class="hoja">
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
                                <tr data-fila>
                                    <td class="num">{{ $rubro['numero'] ?? '' }}@if (! empty($rubro['numero']))<input type="hidden" name="filas[{{ $i }}][numero]" value="{{ $rubro['numero'] }}">@endif</td>
                                    <td><input name="filas[{{ $i }}][descripcion]" value="{{ $rubro['descripcion'] }}"></td>
                                    <td><input class="u" name="filas[{{ $i }}][unidad]" value="{{ $rubro['unidad'] }}"></td>
                                    <td><input class="n cant" name="filas[{{ $i }}][cantidad_contratada]" value="{{ $rubro['cantidad_contratada'] }}"></td>
                                    <td><input class="n precio" name="filas[{{ $i }}][precio_unitario]" value="{{ $rubro['precio_unitario'] }}"></td>
                                    <td class="calc total"></td>
                                    <td>
                                        @if ($contrato->exists && ! empty($rubro['id']))
                                            <button class="danger" type="submit" form="eliminar-rubro-{{ $rubro['id'] }}">Eliminar</button>
                                        @else
                                            <button class="danger" type="button" data-quitar>Eliminar</button>
                                        @endif
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
                                    <td><button class="danger" type="button" data-quitar>Eliminar</button></td>
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
                        <td><button class="danger" type="button" data-quitar>Eliminar</button></td>
                    </tr>
                </template>
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
                <p>Columnas: Descripción, Unidad y Precio unitario. Reemplaza los rubros de este contrato. Las planillas que ya existen no cambian.</p>
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
                tbody.querySelectorAll('tr').forEach(enlazar);
                tbody.addEventListener('click', (evento) => {
                    const boton = evento.target.closest('[data-quitar]');
                    if (boton) boton.closest('tr')?.remove();
                });
                const indices = [...document.querySelectorAll('[name^="filas["]')].map((campo) => {
                    const coincidencia = campo.name.match(/filas\[(\d+)\]/);
                    return coincidencia ? Number(coincidencia[1]) : -1;
                });
                let indice = Math.max(-1, ...indices) + 1;
                const agregarFila = () => {
                    const fila = document.getElementById('fila-nueva').content.cloneNode(true).querySelector('tr');
                    fila.querySelectorAll('[name]').forEach((campo) => {
                        campo.name = campo.name.replace('__i__', String(indice));
                    });
                    indice += 1;
                    const ancla = tbody.querySelector('[data-nuevo]');
                    if (ancla) tbody.insertBefore(fila, ancla);
                    else tbody.appendChild(fila);
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
                            fila.removeAttribute('data-nuevo');
                            fila.querySelector('[name$="[descripcion]"]').value = rubro.descripcion;
                            fila.querySelector('[name$="[unidad]"]').value = rubro.unidad || '';
                            fila.querySelector('[name$="[cantidad_contratada]"]').value = rubro.cantidad_contratada ?? '';
                            fila.querySelector('[name$="[precio_unitario]"]').value = rubro.precio_unitario ?? '';
                            fila.querySelector('.cant')?.dispatchEvent(new Event('input'));
                            agregados += 1;
                        });
                        botonCatalogo.textContent = agregados > 0 ? 'Se agregaron ' + agregados + ' rubros' : 'Esos rubros ya están en el contrato';
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
                        if (fila.hasAttribute('data-nuevo')) {
                            fila.hidden = false;
                            return;
                        }
                        guardados += 1;
                        const coincide = consulta === '' || textoFila(fila).includes(consulta);
                        fila.hidden = !coincide;
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
