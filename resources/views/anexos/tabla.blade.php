@php
    $excel = (int) $anexo->hoja > 1;
    $usarPlantilla = ! $excel && $plantillaMedicion !== [] && \App\Services\HojaCalculo::hojaVacia($anexo);
    $columnasHoja = $excel
        ? \App\Services\HojaCalculo::columnas($anexo->columnas, $tipoMedicion)
        : \App\Services\HojaCalculo::conFormulas(
            $usarPlantilla ? $plantillaMedicion : \App\Services\HojaCalculo::columnas($anexo->columnas, $tipoMedicion),
            $plantillaMedicion,
            $anexo->lineas,
        );
    if ($excel && ($anexo->columnas === null || $anexo->columnas === [] || \App\Services\HojaCalculo::esHojaClasica($columnasHoja))) {
        $columnasHoja = \App\Services\HojaCalculo::columnasExcel();
    }
    if (! $excel && ! $usarPlantilla && \App\Services\HojaCalculo::esHojaClasica($columnasHoja)) {
        $columnasHoja = \App\Services\HojaCalculo::columnasExcel();
    }
    $soloLetras = $excel || \App\Services\HojaCalculo::soloLetras($columnasHoja);
    $filasMedicion = $anexo->lineas->values();
    $totalFilas = max($filasMedicion->count() + 8, 12);
    $prefijo = 'tablas['.$anexo->id.']';
@endphp
<div class="card" data-tabla-medicion>
    <div class="fila">
        <h2>{{ (int) $anexo->hoja === 1 ? 'Mediciones' : 'Subtotal '.$anexo->hoja }}</h2>
        @if ($periodoAbierto && (int) $anexo->hoja > 1)
            <button class="btn-rojo" type="submit" form="quitar-tabla-{{ $anexo->id }}">Quitar tabla</button>
        @endif
    </div>
    <p class="acciones">
        @if ($periodoAbierto && ! $soloLetras)
            <button type="button" data-abrir-formulas>Fórmulas</button>
        @endif
        <button type="button" class="secundario" data-agregar-columna>Agregar columna</button>
        <button type="button" class="secundario" data-agregar-fila>Agregar fila</button>
    </p>
    <div class="excel-barra">
        <span class="excel-ref" data-fx-ref>A1</span>
        <span class="excel-fx">fx</span>
        <input data-fx-input autocomplete="off" placeholder="Valor o fórmula, por ejemplo =B2*2" @disabled(! $periodoAbierto)>
    </div>
    <div class="scroll">
        <table class="hoja" data-hoja data-tipo="{{ $tipoMedicion }}" data-prefijo="{{ $prefijo }}" @if ($soloLetras) data-letras="1" @endif>
            <thead>
                <tr>
                    <th class="esquina"></th>
                    @foreach ($columnasHoja as $indice => $columna)
                        <th data-clave="{{ $columna['clave'] }}" @if (! empty($columna['formula'])) data-formula="{{ $columna['formula'] }}" @endif>
                            <input type="hidden" name="{{ $prefijo }}[orden_columnas][]" value="{{ $columna['clave'] }}">
                            <span class="letra">{{ \App\Services\HojaCalculo::letra($indice) }}</span>
                            @if ($soloLetras)
                                <input type="hidden" class="etiqueta" name="{{ $prefijo }}[etiquetas][{{ $columna['clave'] }}]" value="{{ $columna['etiqueta'] }}">
                            @else
                                <input class="etiqueta" name="{{ $prefijo }}[etiquetas][{{ $columna['clave'] }}]" value="{{ $columna['etiqueta'] }}" maxlength="40" autocomplete="off">
                            @endif
                            @if ($columna['clave'] !== 'descripcion')
                                <input class="formula-col" name="{{ $prefijo }}[formulas][{{ $columna['clave'] }}]" value="{{ $columna['formula'] ?? '' }}" placeholder="Fórmula" maxlength="200" autocomplete="off" tabindex="-1" aria-hidden="true">
                                <span class="formula-vista">{{ $columna['formula'] ?? '' }}</span>
                            @endif
                            @unless ($columna['clave'] === 'total' || $columna['clave'] === 'descripcion')
                                <button type="button" class="quitar-col" title="Quitar columna">×</button>
                            @endunless
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < $totalFilas; $i++)
                    <tr>
                        <td class="fila-marca">
                            <span class="num-fila">{{ $i + 1 }}</span>
                            <button type="button" class="quitar-fila" title="Quitar fila">×</button>
                        </td>
                        @foreach ($columnasHoja as $columna)
                            @php
                                $editor = \App\Services\HojaCalculo::editor($filasMedicion->get($i), $columna['clave'], $tipoMedicion);
                                if ($editor['raw'] === '' && ! empty($columna['formula'])) {
                                    $editor['raw'] = \App\Services\HojaCalculo::formulaEnFila($columna['formula'], $i + 1);
                                    $editor['visible'] = $editor['raw'];
                                } elseif ($columna['clave'] !== 'descripcion' && $editor['raw'] === '') {
                                    $editor['visible'] = '';
                                }
                            @endphp
                            <td data-clave="{{ $columna['clave'] }}">
                                <input class="{{ $columna['clave'] === 'descripcion' ? 'celda' : 'n celda' }}" @if ($columna['clave'] === 'descripcion') data-texto="1" @endif @if ($soloLetras && $columna['clave'] === 'descripcion') placeholder="Descripción" @endif data-raw="{{ $editor['raw'] }}" value="{{ $editor['visible'] }}" autocomplete="off">
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
