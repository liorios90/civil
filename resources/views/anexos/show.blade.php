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
        $periodoAbierto = $periodoAbierto ?? false;
    @endphp
    @unless ($periodoAbierto)
        <div class="alerta">Esta planilla ya no se puede modificar. Solo se puede visualizar.</div>
    @endunless

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
        if (! $usarPlantilla && \App\Services\HojaCalculo::esHojaClasica($columnasHoja)) {
            $columnasHoja = \App\Services\HojaCalculo::columnasExcel();
        }
        $soloLetras = \App\Services\HojaCalculo::soloLetras($columnasHoja);
        $filasMedicion = $anexo->lineas->values();
        $totalFilas = max($filasMedicion->count() + 8, 12);
    @endphp
    <style>
        table.hoja[data-hoja] { width: max-content; min-width: 100%; table-layout: fixed; }
        table.hoja[data-hoja] th { color: #fff; vertical-align: bottom; min-width: 88px; max-width: 160px; }
        table.hoja[data-hoja] th[data-clave="descripcion"] { min-width: 160px; max-width: 280px; }
        table.hoja[data-hoja] td { min-width: 88px; max-width: 160px; }
        table.hoja[data-hoja] td[data-clave="descripcion"] { min-width: 160px; max-width: 280px; }
        table.hoja[data-hoja] th .letra { display: block; font-size: 10px; font-weight: 400; opacity: .75; }
        table.hoja[data-letras="1"] th .letra { font-size: 15px; font-weight: 700; opacity: 1; letter-spacing: .04em; }
        table.hoja[data-hoja] th input.etiqueta { display: block; width: 100%; min-width: 0; max-width: 100%; box-sizing: border-box; background: transparent; color: #fff; border: 0; border-bottom: 1px solid rgba(255,255,255,.45); border-radius: 0; text-align: center; padding: 2px 4px; font-weight: 700; }
        table.hoja[data-hoja] th input.formula-col { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        table.hoja[data-hoja] th .formula-vista { display: block; margin-top: 4px; font-size: 11px; font-weight: 400; opacity: .9; color: #d7e8f8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
        table.hoja[data-hoja] th .formula-vista:empty { display: none; }
        table.hoja[data-hoja] th button.quitar-col { background: transparent; color: #fff; padding: 0 4px; min-width: 0; font-size: 16px; line-height: 1; }
        .modal-fondo { position: fixed; inset: 0; z-index: 80; background: rgba(15, 33, 51, .48); display: flex; align-items: center; justify-content: center; padding: 16px; }
        .modal-fondo[hidden] { display: none; }
        .modal-formulas { width: min(720px, 100%); max-height: min(88vh, 820px); overflow: auto; background: #fff; border-radius: 14px; box-shadow: 0 18px 50px rgba(15, 33, 51, .28); border: 1px solid #d5dde5; }
        .modal-formulas .modal-cabeza { padding: 18px 20px 12px; border-bottom: 1px solid #e7eef5; display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; background: linear-gradient(180deg, #f7fafc 0%, #fff 100%); }
        .modal-formulas .modal-cabeza h2 { margin: 0 0 4px; font-size: 18px; color: #0f3d68; }
        .modal-formulas .modal-cabeza p { margin: 0; color: #4a5b6d; font-size: 13px; line-height: 1.4; max-width: 52ch; }
        .modal-formulas .cerrar-modal { background: transparent; color: #4a5b6d; font-size: 22px; line-height: 1; padding: 4px 8px; min-width: 0; }
        .modal-formulas .modal-cuerpo { padding: 14px 20px 8px; display: grid; gap: 12px; }
        .modal-formulas .aviso-solo { margin: 0; padding: 10px 12px; border-radius: 8px; background: #eef4fb; color: #0f3d68; font-size: 13px; border: 1px solid #d5e3f2; }
        .modal-formulas .dato-formula { border: 1px solid #d9e0e7; border-radius: 10px; padding: 12px 14px; background: #fbfcfd; }
        .modal-formulas .dato-formula.activo { border-color: #0f3d68; background: #f4f8fc; box-shadow: inset 0 0 0 1px rgba(15,61,104,.12); }
        .modal-formulas .dato-tope { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; }
        .modal-formulas .dato-tope strong { color: #0f3d68; font-size: 14px; }
        .modal-formulas .dato-tope .letra-chip { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 24px; padding: 0 8px; border-radius: 6px; background: #1f4e79; color: #fff; font-size: 12px; font-weight: 700; }
        .modal-formulas .campo-formula { display: grid; gap: 6px; }
        .modal-formulas .campo-formula label { font-size: 12px; color: #4a5b6d; }
        .modal-formulas .campo-formula input { font-family: Consolas, "Courier New", monospace; font-size: 14px; }
        .modal-formulas .chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .modal-formulas .chips button { background: #fff; color: #0f3d68; border: 1px solid #c5d0db; border-radius: 8px; padding: 6px 10px; font-size: 12px; font-weight: 600; }
        .modal-formulas .chips button:hover { border-color: #0f3d68; background: #eef4fb; }
        .modal-formulas .chips button.op { min-width: 36px; font-family: Consolas, "Courier New", monospace; }
        .modal-formulas .chips button.fn { background: #e8eef5; }
        .modal-formulas .ayuda-formula { margin: 4px 0 0; font-size: 12px; color: #5c6d7e; }
        .modal-formulas .modal-pie { padding: 12px 20px 18px; border-top: 1px solid #e7eef5; display: flex; justify-content: flex-end; gap: 8px; background: #fafbfc; }
        table.hoja[data-hoja] th.esquina, table.hoja[data-hoja] td.fila-marca { position: sticky; left: 0; width: 52px; min-width: 52px; max-width: 52px; text-align: center; vertical-align: middle; }
        table.hoja[data-hoja] th.esquina { z-index: 2; background: #1f4e79; }
        table.hoja[data-hoja] td.fila-marca { z-index: 1; background: #e7eef5; color: #1f4e79; padding: 4px 2px; }
        table.hoja[data-hoja] td.fila-marca .num-fila { display: block; font-weight: 700; font-size: 12px; }
        table.hoja[data-hoja] td.fila-marca button.quitar-fila { background: transparent; color: #9b1c1c; padding: 0 4px; min-width: 0; font-size: 16px; line-height: 1; }
        table.hoja[data-hoja] td input.celda,
        table.hoja[data-hoja] td input.n,
        table.hoja[data-hoja] td input { width: 100%; min-width: 0; max-width: 100%; box-sizing: border-box; padding: 6px 4px; }
        table.hoja[data-hoja] input.celda.malo { color: #9b1c1c; background: #fdecec; }
        table.hoja[data-hoja] td.activa { outline: 2px solid #1d6f42; outline-offset: -2px; }
        table.hoja[data-hoja] td.con-formula { background: #eef6ea; }
        table.hoja[data-hoja] td.con-formula input { background: transparent; }
        table.hoja[data-hoja] tfoot td { background: #eef3ea; font-weight: 700; color: #1f4e79; padding: 8px; text-align: right; }
        table.hoja[data-hoja] tfoot td.suma-etiqueta { text-align: center; background: #e7eef5; }
        .excel-barra { display: flex; align-items: stretch; gap: 0; margin: 0 0 8px; border: 1px solid #c5d0db; border-radius: 8px; overflow: hidden; background: #fff; }
        .excel-barra .excel-ref { flex: none; min-width: 64px; display: flex; align-items: center; justify-content: center; padding: 0 10px; background: #e7eef5; color: #0f3d68; font-weight: 700; font-size: 13px; border-right: 1px solid #c5d0db; }
        .excel-barra .excel-fx { flex: none; display: flex; align-items: center; padding: 0 10px; color: #1d6f42; font-weight: 700; font-style: italic; border-right: 1px solid #c5d0db; }
        .excel-barra input { border: 0; border-radius: 0; font-family: Consolas, "Courier New", monospace; }
    </style>
    <form method="post" action="{{ route('anexos.guardar', $ejecucion) }}" data-tipo="{{ $tipoMedicion }}">
        @csrf
        <fieldset @disabled(! $periodoAbierto) style="border:0;margin:0;padding:0">
        <div class="card">
            <h2>Mediciones</h2>
            <p>@if ($soloLetras) Escriba la descripción en la columna A. En las demás columnas escriba un número o una fórmula, por ejemplo =B2*2. @else {{ $explicaMedicion }} Si la columna tiene fórmula, se ve el resultado. Si no tiene, escriba en la celda un número o una fórmula. @endif La suma de la última columna es la cantidad que pasa a la planilla del frente.</p>
            <p class="acciones">
                @if ($periodoAbierto)
                    <button type="button" data-abrir-formulas>Fórmulas</button>
                @endif
                <button type="button" class="secundario" data-agregar-columna>Agregar columna</button>
                <button type="button" class="secundario" data-agregar-fila>Agregar fila</button>
            </p>
            <div class="excel-barra">
                <span class="excel-ref" data-fx-ref>A1</span>
                <span class="excel-fx">fx</span>
                <input data-fx-input autocomplete="off" placeholder="Valor o fórmula de la celda, por ejemplo =altura*peso" @disabled(! $periodoAbierto)>
            </div>
            <div class="scroll">
                <table class="hoja" data-hoja data-tipo="{{ $tipoMedicion }}" @if ($soloLetras) data-letras="1" @endif>
                    <thead>
                        <tr>
                            <th class="esquina"></th>
                            @foreach ($columnasHoja as $indice => $columna)
                                <th data-clave="{{ $columna['clave'] }}" @if (! empty($columna['formula'])) data-formula="{{ $columna['formula'] }}" @endif>
                                    <input type="hidden" name="orden_columnas[]" value="{{ $columna['clave'] }}">
                                    <span class="letra">{{ \App\Services\HojaCalculo::letra($indice) }}</span>
                                    @if ($soloLetras)
                                        <input type="hidden" class="etiqueta" name="etiquetas[{{ $columna['clave'] }}]" value="{{ $columna['etiqueta'] }}">
                                    @else
                                        <input class="etiqueta" name="etiquetas[{{ $columna['clave'] }}]" value="{{ $columna['etiqueta'] }}" maxlength="40" autocomplete="off">
                                    @endif
                                    @if ($columna['clave'] !== 'descripcion')
                                        <input class="formula-col" name="formulas[{{ $columna['clave'] }}]" value="{{ $columna['formula'] ?? '' }}" placeholder="Fórmula" maxlength="200" autocomplete="off" tabindex="-1" aria-hidden="true">
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
                                        <input type="hidden" class="crudo" name="lineas[{{ $i }}][celdas][{{ $columna['clave'] }}]" value="{{ $editor['raw'] }}">
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

    @if ($periodoAbierto)
        <div class="modal-fondo" id="modal-formulas" hidden>
            <div class="modal-formulas" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-formulas">
                <div class="modal-cabeza">
                    <div>
                        <h2 id="titulo-modal-formulas">Fórmulas de esta medición</h2>
                        <p>Arme cada cálculo con los nombres de las columnas. Los cambios valen solo para esta hoja de esta planilla.</p>
                    </div>
                    <button type="button" class="cerrar-modal" data-cerrar-formulas title="Cerrar">×</button>
                </div>
                <div class="modal-cuerpo">
                    <p class="aviso-solo">No modifica el catálogo ni otras planillas. Al guardar mediciones se conserva aquí.</p>
                    <div data-lista-formulas></div>
                </div>
                <div class="modal-pie">
                    <button type="button" class="secundario" data-cerrar-formulas>Cancelar</button>
                    <button type="button" data-aplicar-formulas>Aplicar a esta hoja</button>
                </div>
            </div>
        </div>
    @endif

    @php($hojaJs = public_path('js/hoja-calculo.js'))
    @if (is_file($hojaJs))
        <script src="{{ asset('js/hoja-calculo.js') }}?v={{ filemtime($hojaJs) }}"></script>
    @endif
@endsection
