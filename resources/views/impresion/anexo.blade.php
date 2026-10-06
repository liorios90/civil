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
        @php
            $hoja = \App\Services\HojaCalculo::presentar($anexo, \App\Services\UnidadMedicion::tipo($rubro->unidad));
        @endphp
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
                    @foreach ($hoja['columnas'] as $columna)
                        <th>{{ $columna['etiqueta'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($hoja['filas'] as $fila)
                    <tr>
                        @foreach ($hoja['columnas'] as $columna)
                            <td class="{{ $columna['clave'] === 'descripcion' ? '' : 'n' }}">{{ $fila[$columna['clave']] }}</td>
                        @endforeach
                    </tr>
                @endforeach
                @for ($i = count($hoja['filas']); $i < 8; $i++)
                    <tr>@foreach ($hoja['columnas'] as $columna)<td>&nbsp;</td>@endforeach</tr>
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
