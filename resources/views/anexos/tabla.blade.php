@php
    $guardadas = is_array($anexo->columnas) ? $anexo->columnas : [];
    $propias = $guardadas !== [] && collect($guardadas)->every(function ($columna) {
        if (! is_array($columna) || trim((string) ($columna['formula'] ?? '')) !== '') {
            return false;
        }
        $clave = strtolower((string) ($columna['clave'] ?? ''));

        return in_array($clave, \App\Services\HojaCalculo::FIJAS, true) || preg_match('/^c[a-z0-9]+$/', $clave) === 1;
    });
    $columnasHoja = $propias
        ? \App\Services\HojaCalculo::columnas($guardadas, $tipoMedicion)
        : \App\Services\HojaCalculo::columnasIniciales($tipoMedicion);
    $filasMedicion = $anexo->lineas->values();
    $totalFilas = max($filasMedicion->count() + 8, 12);
    $prefijo = 'tablas['.$anexo->id.']';
    $porClave = collect($columnasHoja)->keyBy('clave');
    $calculada = ['longitud', 'area', 'volumen', 'total'];
@endphp
<div class="card" data-tabla-medicion>
    <div class="fila">
        <h2>{{ (int) $anexo->hoja === 1 ? 'Mediciones' : 'Subtotal '.$anexo->hoja }}</h2>
        @if ($periodoAbierto && (int) $anexo->hoja > 1)
            <button class="btn-rojo" type="submit" form="quitar-tabla-{{ $anexo->id }}">Quitar tabla</button>
        @endif
    </div>
    <p class="acciones">
        <button type="button" class="secundario" data-agregar-columna>Agregar columna</button>
        <button type="button" class="secundario" data-agregar-fila>Agregar fila</button>
    </p>
    <div class="scroll">
        <table class="hoja" data-hoja data-tipo="{{ $tipoMedicion }}" data-prefijo="{{ $prefijo }}" data-clasica="1" data-letras="1">
            <thead>
                <tr>
                    <th class="esquina"></th>
                    @foreach ($columnasHoja as $indice => $columna)
                        <th data-clave="{{ $columna['clave'] }}">
                            <span class="letra">{{ \App\Services\HojaCalculo::letra($indice) }}</span>
                            <input type="hidden" name="{{ $prefijo }}[orden_columnas][]" value="{{ $columna['clave'] }}">
                            <input class="etiqueta" name="{{ $prefijo }}[etiquetas][{{ $columna['clave'] }}]" value="{{ $columna['etiqueta'] }}" maxlength="40" autocomplete="off" title="Título de la columna">
                            <span class="cabecera-botones">
                                <button type="button" class="insertar-col" title="Insertar columna a la derecha">+</button>
                                <button type="button" class="quitar-col" title="Quitar columna">×</button>
                            </span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < $totalFilas; $i++)
                    <tr>
                        <td class="fila-marca">
                            <span class="num-fila">{{ $i + 1 }}</span>
                            <button type="button" class="insertar-fila" title="Insertar fila debajo">+</button>
                            <button type="button" class="quitar-fila" title="Quitar fila">×</button>
                        </td>
                        @foreach ($columnasHoja as $columna)
                            @php
                                $editor = \App\Services\HojaCalculo::editor($filasMedicion->get($i), $columna['clave'], $tipoMedicion);
                                if (! in_array($columna['clave'], $calculada, true) && $editor['raw'] === '' && $editor['visible'] !== '') {
                                    $editor['raw'] = $editor['visible'];
                                }
                                if (in_array($columna['clave'], $calculada, true) && $editor['raw'] === '') {
                                    $editor['visible'] = '';
                                }
                            @endphp
                            <td data-clave="{{ $columna['clave'] }}" @class(['calc' => in_array($columna['clave'], $calculada, true)])>
                                <input class="{{ $columna['clave'] === 'descripcion' ? 'celda' : 'n celda' }}" @if ($columna['clave'] === 'descripcion') data-texto="1" placeholder="Descripción" @endif data-raw="{{ $editor['raw'] }}" value="{{ $editor['visible'] }}" autocomplete="off">
                                <input type="hidden" class="crudo" name="{{ $prefijo }}[lineas][{{ $i }}][celdas][{{ $columna['clave'] }}]" value="{{ $editor['raw'] }}">
                            </td>
                        @endforeach
                    </tr>
                @endfor
            </tbody>
            <tfoot>
                <tr>
                    <td class="suma-etiqueta">Suma</td>
                    @foreach ($columnasHoja as $columna)
                        <td @if ($loop->last) class="suma-final" data-suma @endif>@if ($loop->last) 0.00 @endif</td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    </div>
</div>
