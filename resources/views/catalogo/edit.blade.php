@extends('layout')

@section('titulo', 'Catálogo de rubros')

@section('contenido')
    <p><a class="btn secundario" href="{{ route('inicio') }}">Volver a la pantalla principal</a></p>
    <div class="card">
        <h1>Catálogo general de rubros</h1>
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
        <p>Estos rubros son de su empresa. En un contrato puede traerlos y quitar los que no use. Cambiar el catálogo no modifica los contratos que ya existen.</p>
        <p>En Medición puede indicar qué datos se escriben y qué fórmula calcula el total. Si un rubro no tiene medición, al planillar se abre la hoja normal.</p>
        <form method="post" action="{{ route('catalogo.update') }}">
            @csrf
            @method('put')
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
                        @foreach ($rubros as $i => $rubro)
                            <tr data-fila>
                                <td class="num">{{ $rubro->numero }}<input type="hidden" name="filas[{{ $i }}][numero]" value="{{ $rubro->numero }}"></td>
                                <td><input name="filas[{{ $i }}][descripcion]" value="{{ $rubro->descripcion }}"></td>
                                <td><input class="u" name="filas[{{ $i }}][unidad]" value="{{ $rubro->unidad }}"></td>
                                <td><input class="n cant" name="filas[{{ $i }}][cantidad_contratada]" value="{{ $rubro->cantidad_contratada + 0 }}"></td>
                                <td><input class="n precio" name="filas[{{ $i }}][precio_unitario]" value="{{ $rubro->precio_unitario + 0 }}"></td>
                                <td class="calc total"></td>
                                <td><button class="danger" type="button" data-quitar>Eliminar</button></td>
                            </tr>
                            <tr data-config>
                                <td colspan="7">
                                    @include('catalogo.medicion', ['indice' => $i, 'medicion' => $rubro->medicion ?? []])
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
                                <td><button class="danger" type="button" data-quitar>Eliminar</button></td>
                            </tr>
                            <tr data-config>
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
                    <td><button class="danger" type="button" data-quitar>Eliminar</button></td>
                </tr>
                <tr data-config>
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
            <p class="acciones"><button type="submit">Guardar catálogo</button></p>
        </form>
    </div>
    <form method="post" action="{{ route('catalogo.excel') }}" enctype="multipart/form-data" class="card" onsubmit="return confirm('El Excel reemplaza el catálogo general. Los contratos que ya existen no cambian.')">
        @csrf
        <h2>Subir el catálogo desde Excel</h2>
        <p>Columnas: Descripción, Unidad y Precio unitario. También puede incluir Cantidad y Número.</p>
        <p><input type="file" name="rubros_excel" accept=".xlsx,.xls" required></p>
        <p><button type="submit">Cargar Excel</button></p>
    </form>
    <style>
        .medicion { margin: 4px 0 12px; padding: 10px 12px; background: #f7fafc; border: 1px solid #d5dde5; border-radius: 8px; }
        .medicion-titulo { margin: 0 0 4px; font-weight: 700; }
        .medicion-ayuda { margin: 0 0 8px; color: #4a5b6d; font-size: 13px; }
        .dato { display: grid; grid-template-columns: 1fr 1fr auto; gap: 8px; align-items: center; margin-bottom: 6px; }
        .dato-fijo { grid-template-columns: 1fr 1fr; }
        .dato-fijo input { background: #eef3f7; }
        .medicion-fijo { color: #4a5b6d; font-size: 13px; }
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
        tbody.addEventListener('click', (evento) => {
            const quitarDato = evento.target.closest('[data-quitar-dato]');
            if (quitarDato) {
                quitarDato.closest('[data-dato]')?.remove();
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
            const lista = boton.parentElement.parentElement.querySelector('[data-datos]');
            const j = lista.querySelectorAll('[data-dato]').length;
            const dato = document.getElementById('dato-nuevo').content.cloneNode(true).querySelector('[data-dato]');
            dato.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace('__i__', i).replace('__j__', String(j));
            });
            lista.appendChild(dato);
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
