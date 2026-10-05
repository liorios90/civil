@extends('impresion.layout')

@section('titulo', 'Anexo rubro '.$ejecucion->rubro->numero)

@section('extra')
    @media print { @page { size: portrait; margin: 10mm; } }
@endsection

@section('contenido')
    @php
        $c = $ejecucion->planilla->contrato;
        $rubro = $ejecucion->rubro;
        $q = fn ($v) => $v === null || $v === '' ? '' : number_format((float) $v, 2, '.', '');
        $porEjecutar = round((float) $rubro->cantidad_contratada - (float) $calculo['cantidad_total'], 2);
        $imagenesOtras = $anexo->imagenes->filter(fn ($imagen) => str_starts_with($imagen->ruta, 'anexos/otras/'));
        $imagenesIniciales = $anexo->imagenes->reject(fn ($imagen) => str_starts_with($imagen->ruta, 'anexos/otras/'));
    @endphp
    <div class="cabecera centro">
        <h1>MUNICIPIO DEL DISTRITO METROPOLITANO DE QUITO</h1>
        <p>ADMINISTRACIÓN ZONAL EUGENIO ESPEJO</p>
        <p>JEFATURA DE FISCALIZACIÓN</p>
        <h2>ANEXO DE CANTIDAD DE OBRA &nbsp; No. {{ $rubro->numero }}</h2>
    </div>
    <table style="margin-bottom:8px">
        <tr><td><b>Obra:</b> {{ $rubro->frente->nombre }}</td><td><b>Código:</b> {{ $c->codigo_proceso }}</td></tr>
        <tr><td><b>Contratista:</b> {{ $c->contratista }}</td><td><b>Fiscalizador:</b> {{ $c->fiscalizador }}</td></tr>
        <tr><td colspan="2"><b>Rubro:</b> {{ $rubro->descripcion }} &nbsp; <b>Unidad:</b> {{ $rubro->unidad }}</td></tr>
        <tr><td colspan="2">{{ \App\Services\UnidadMedicion::explica(\App\Services\UnidadMedicion::tipo($rubro->unidad)) }}</td></tr>
    </table>

    @if ($imagenesIniciales->isNotEmpty())
        <p><b>Imágenes</b></p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0">
            @foreach ($imagenesIniciales as $imagen)
                <img src="{{ url('archivos/'.$imagen->ruta) }}" alt="Imagen" style="width:220px;height:160px;object-fit:cover;border:1px solid #000">
            @endforeach
        </div>
    @endif
        <table>
            <thead>
                <tr>
                    <th rowspan="2">Descripción</th>
                    <th colspan="4">Dimensiones</th>
                    <th colspan="3">Subtotales</th>
                    <th rowspan="2">Total</th>
                </tr>
                <tr>
                    <th>Base 1</th><th>Base 2</th><th>Altura</th><th>{{ \App\Services\UnidadMedicion::tipo($rubro->unidad) === 'm3km' ? 'Km' : 'Número' }}</th>
                    <th>Longitud</th><th>Área</th><th>Volumen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($anexo->lineas as $linea)
                    <tr>
                        <td>{{ $linea->descripcion }}</td>
                        <td class="n">{{ $q($linea->base1) }}</td>
                        <td class="n">{{ $q($linea->base2) }}</td>
                        <td class="n">{{ $q($linea->altura) }}</td>
                        <td class="n">{{ $q($linea->numero) }}</td>
                        <td class="n">{{ $q($linea->longitud) }}</td>
                        <td class="n">{{ $q($linea->area) }}</td>
                        <td class="n">{{ $q($linea->volumen) }}</td>
                        <td class="n">{{ $q($linea->total) }}</td>
                    </tr>
                @endforeach
                @for ($i = $anexo->lineas->count(); $i < 12; $i++)
                    <tr>@for ($columna = 0; $columna < 9; $columna++)<td>&nbsp;</td>@endfor</tr>
                @endfor
            </tbody>
        </table>
        <table style="margin-top:8px;width:320px;margin-left:auto">
            <tr><td>TOTAL A FACTURAR</td><td class="n">{{ $q($ejecucion->cantidad_actual) }}</td></tr>
            <tr><td>ANTERIOR</td><td class="n">{{ $q($ejecucion->cantidad_anterior) }}</td></tr>
            <tr><td>TOTAL</td><td class="n">{{ $q($calculo['cantidad_total']) }}</td></tr>
            <tr><td>CONTRACTUAL</td><td class="n">{{ $q($rubro->cantidad_contratada) }}</td></tr>
            <tr><td>POR EJECUTAR</td><td class="n">{{ $q($porEjecutar) }}</td></tr>
        </table>
    @if ($imagenesOtras->isNotEmpty())
        <p><b>Otras imágenes</b></p>
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
            @foreach ($imagenesOtras as $imagen)
                <img src="{{ url('archivos/'.$imagen->ruta) }}" alt="Otra imagen" style="width:220px;height:160px;object-fit:cover;border:1px solid #000">
            @endforeach
        </div>
    @endif

    <div class="firmas">
        <div>{{ $c->contratista }}<br>CONTRATISTA</div>
        <div>{{ $c->fiscalizador }}<br>FISCALIZADOR</div>
    </div>
@endsection
