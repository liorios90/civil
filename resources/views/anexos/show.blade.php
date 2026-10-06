@extends('layout')

@section('titulo', 'Hoja rubro '.$ejecucion->rubro->numero)

@section('contenido')
    <p>
        <a href="{{ route('frentes.show', $ejecucion->rubro->frente) }}">Volver a la planilla</a>
        · <a href="{{ route('impresion.anexo', $ejecucion) }}" target="_blank">Imprimir hoja</a>
    </p>
    @if (session('estado'))
        <div class="alerta">{{ session('estado') }}</div>
    @endif
    <div class="card">
        <strong>Rubro {{ $ejecucion->rubro->numero }}.</strong> {{ $ejecucion->rubro->descripcion }}
        · Unidad {{ $ejecucion->rubro->unidad }}
        · Total a facturar: {{ number_format((float) $ejecucion->cantidad_actual, 2, '.', '') }}
    </div>
    @include('historial.resumen', [
        'creado' => null,
        'filtro' => ['planilla_rubro_id' => $ejecucion->id],
        'enlace' => route('historial.index', [$ejecucion->planilla->contrato, 'ejecucion' => $ejecucion->id]),
    ])

    @php
        $imagenesOtras = $anexo->imagenes->filter(fn ($imagen) => str_starts_with($imagen->ruta, 'anexos/otras/'));
        $imagenesIniciales = $anexo->imagenes->reject(fn ($imagen) => str_starts_with($imagen->ruta, 'anexos/otras/'));
        $periodoAbierto = true;
    @endphp

    <form method="post" action="{{ route('anexos.imagenes', $ejecucion) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="seccion" value="inicial">
        <fieldset @disabled(! $periodoAbierto) style="border:0;margin:0;padding:0">
        <div class="card">
            <h2>Imágenes</h2>
            <p><label>Cargar imágenes<br><input type="file" name="imagenes[]" accept="image/*" multiple></label></p>
            <button type="submit">Guardar imágenes</button>
        </div>
        </fieldset>
    </form>
    <div class="galeria">
        @forelse ($imagenesIniciales as $imagen)
            <figure>
                <img src="{{ url('archivos/'.$imagen->ruta) }}" alt="Imagen">
                @if ($periodoAbierto)
                <form method="post" action="{{ route('anexos.imagenes.destroy', [$ejecucion, $imagen]) }}">
                    @csrf
                    @method('delete')
                    <button class="danger" type="submit">Quitar</button>
                </form>
                @endif
            </figure>
        @empty
            <p>Todavía no hay imágenes en esta sección.</p>
        @endforelse
    </div>

    @php
        $tipoMedicion = \App\Services\UnidadMedicion::tipo($ejecucion->rubro->unidad);
        $explicaMedicion = \App\Services\UnidadMedicion::explica($tipoMedicion);
        $plantillaMedicion = \App\Services\HojaCalculo::plantillaPara($ejecucion->rubro);
        $usarPlantilla = $plantillaMedicion !== [] && \App\Services\HojaCalculo::hojaVacia($anexo);
        $columnasHoja = \App\Services\HojaCalculo::conFormulas(
            $usarPlantilla ? $plantillaMedicion : \App\Services\HojaCalculo::columnas($anexo->columnas, $tipoMedicion),
            $plantillaMedicion,
            $anexo->lineas,
        );
        $filasMedicion = $anexo->lineas->values();
        $totalFilas = max($filasMedicion->count() + 8, 12);
    @endphp
    <style>
        table.hoja th { color: #fff; vertical-align: bottom; }
        table.hoja th .letra { display: block; font-size: 10px; font-weight: 400; opacity: .75; }
        table.hoja th input.etiqueta { min-width: 72px; width: 88px; background: transparent; color: #fff; border: 0; border-bottom: 1px solid rgba(255,255,255,.45); border-radius: 0; text-align: center; padding: 2px 4px; font-weight: 700; }
        table.hoja th input.formula-col { display: block; width: 100%; min-width: 72px; margin-top: 4px; background: rgba(255,255,255,.14); color: #fff; border: 0; border-radius: 4px; font-size: 11px; font-weight: 400; text-align: center; padding: 2px 4px; }
        table.hoja th input.formula-col::placeholder { color: rgba(255,255,255,.75); }
        table.hoja th button.quitar-col { background: transparent; color: #fff; padding: 0 4px; min-width: 0; font-size: 16px; line-height: 1; }
        table.hoja th.esquina, table.hoja td.fila-marca { position: sticky; left: 0; width: 52px; min-width: 52px; text-align: center; vertical-align: middle; }
        table.hoja th.esquina { z-index: 2; background: #1f4e79; }
        table.hoja td.fila-marca { z-index: 1; background: #e7eef5; color: #1f4e79; padding: 4px 2px; }
        table.hoja td.fila-marca .num-fila { display: block; font-weight: 700; font-size: 12px; }
        table.hoja td.fila-marca button.quitar-fila { background: transparent; color: #9b1c1c; padding: 0 4px; min-width: 0; font-size: 16px; line-height: 1; }
        table.hoja input.celda.malo { color: #9b1c1c; background: #fdecec; }
    </style>
    <form method="post" action="{{ route('anexos.guardar', $ejecucion) }}" data-tipo="{{ $tipoMedicion }}">
        @csrf
        <fieldset @disabled(! $periodoAbierto) style="border:0;margin:0;padding:0">
        <div class="card">
            <h2>Mediciones</h2>
            <p>{{ $explicaMedicion }} @if ($usarPlantilla) Esta hoja usa la medición guardada en el rubro del contrato. Escriba la descripción y los datos. Las fórmulas usan los nombres, por ejemplo =altura*peso. @else Escriba un número o una fórmula, por ejemplo =B2*C2 o =SUMA(B2:B8). @endif La fórmula del encabezado se copia en todas las filas. Si una fila necesita otra, cámbiela solo en esa fila. Puede cambiar el nombre de cada columna, agregar columnas o quitarlas, y quitar filas con la × de su número. La descripción siempre está y la columna Total es la cantidad que se factura.</p>
            <p class="acciones">
                <button type="button" class="secundario" data-agregar-columna>Agregar columna</button>
                <button type="button" class="secundario" data-agregar-fila>Agregar fila</button>
            </p>
            <div class="scroll">
                <table class="hoja" data-hoja data-tipo="{{ $tipoMedicion }}">
                    <thead>
                        <tr>
                            <th class="esquina"></th>
                            @foreach ($columnasHoja as $indice => $columna)
                                <th data-clave="{{ $columna['clave'] }}" @if (! empty($columna['formula'])) data-formula="{{ $columna['formula'] }}" @endif>
                                    <input type="hidden" name="orden_columnas[]" value="{{ $columna['clave'] }}">
                                    <span class="letra">{{ \App\Services\HojaCalculo::letra($indice) }}</span>
                                    <input class="etiqueta" name="etiquetas[{{ $columna['clave'] }}]" value="{{ $columna['etiqueta'] }}" maxlength="40" autocomplete="off">
                                    @if ($columna['clave'] !== 'descripcion')
                                        <input class="formula-col" name="formulas[{{ $columna['clave'] }}]" value="{{ $columna['formula'] ?? '' }}" placeholder="Fórmula" maxlength="200" autocomplete="off">
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
                                        }
                                    @endphp
                                    <td data-clave="{{ $columna['clave'] }}">
                                        <input class="{{ $columna['clave'] === 'descripcion' ? 'celda' : 'n celda' }}" @if ($columna['clave'] === 'descripcion') data-texto="1" @endif data-raw="{{ $editor['raw'] }}" value="{{ $editor['visible'] }}" autocomplete="off">
                                        <input type="hidden" class="crudo" name="lineas[{{ $i }}][celdas][{{ $columna['clave'] }}]" value="{{ $editor['raw'] }}">
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
        <div class="barra"><button type="submit">Guardar mediciones</button></div>
        </fieldset>
    </form>

    <form method="post" action="{{ route('anexos.imagenes', $ejecucion) }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="seccion" value="otras">
        <fieldset @disabled(! $periodoAbierto) style="border:0;margin:0;padding:0">
        <div class="card">
            <h2>Otras imágenes</h2>
            <p><label>Cargar imágenes<br><input type="file" name="imagenes[]" accept="image/*" multiple></label></p>
            <button type="submit">Guardar imágenes</button>
        </div>
        </fieldset>
    </form>
    <div class="galeria">
        @forelse ($imagenesOtras as $imagen)
            <figure>
                <img src="{{ url('archivos/'.$imagen->ruta) }}" alt="Otra imagen">
                @if ($periodoAbierto)
                <form method="post" action="{{ route('anexos.imagenes.destroy', [$ejecucion, $imagen]) }}">
                    @csrf
                    @method('delete')
                    <button class="danger" type="submit">Quitar</button>
                </form>
                @endif
            </figure>
        @empty
            <p>Todavía no hay otras imágenes.</p>
        @endforelse
    </div>

    <script src="{{ asset('js/hoja-calculo.js') }}?v={{ filemtime(public_path('js/hoja-calculo.js')) }}"></script>
@endsection
