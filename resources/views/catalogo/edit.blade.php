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
        document.getElementById('agregar-rubro').addEventListener('click', () => {
            const fila = document.getElementById('fila-nueva').content.cloneNode(true).querySelector('tr');
            fila.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace('__i__', String(indice));
            });
            indice += 1;
            tbody.appendChild(fila);
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
                if (fila.hasAttribute('data-nuevo')) {
                    fila.hidden = false;
                    return;
                }
                guardados += 1;
                const coincide = consulta === '' || textoFila(fila).includes(consulta);
                fila.hidden = !coincide;
                if (coincide) visibles += 1;
            });
            aviso.hidden = consulta === '' || visibles > 0 || guardados === 0;
        });
        buscar.addEventListener('keydown', (evento) => {
            if (evento.key === 'Enter') evento.preventDefault();
        });
    </script>
@endsection
