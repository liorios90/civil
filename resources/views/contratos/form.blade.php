@extends('layout')

@section('titulo', $contrato->exists ? $contrato->codigo_proceso : 'Nuevo contrato')

@section('contenido')
    <div class="card">
        <h1>{{ $contrato->exists ? 'Datos del contrato' : 'Nuevo contrato' }}</h1>
        @if ($errors->any())
            <div class="alerta">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        <form method="post" action="{{ $contrato->exists ? route('contratos.update', $contrato) : route('contratos.store') }}">
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
                            'numero' => $rubro->numero,
                            'descripcion' => $rubro->descripcion,
                            'unidad' => $rubro->unidad,
                            'cantidad_contratada' => $rubro->cantidad_contratada + 0,
                            'precio_unitario' => $rubro->precio_unitario + 0,
                        ])->values();
                    }
                @endphp
                <h2>Rubros del contrato</h2>
                <p>Estos rubros se copian en cada frente general que crees.</p>
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
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($filasRubro as $i => $rubro)
                                <tr>
                                    <td class="num">{{ $rubro['numero'] ?? '' }}@if (! empty($rubro['numero']))<input type="hidden" name="filas[{{ $i }}][numero]" value="{{ $rubro['numero'] }}">@endif</td>
                                    <td><input name="filas[{{ $i }}][descripcion]" value="{{ $rubro['descripcion'] }}"></td>
                                    <td><input class="u" name="filas[{{ $i }}][unidad]" value="{{ $rubro['unidad'] }}"></td>
                                    <td><input class="n cant" name="filas[{{ $i }}][cantidad_contratada]" value="{{ $rubro['cantidad_contratada'] }}"></td>
                                    <td><input class="n precio" name="filas[{{ $i }}][precio_unitario]" value="{{ $rubro['precio_unitario'] }}"></td>
                                    <td class="calc total"></td>
                                </tr>
                            @endforeach
                            @for ($n = 0; $n < ($filasRubro->isEmpty() ? 8 : 4); $n++)
                                @php($i = $filasRubro->count() + $n)
                                <tr>
                                    <td class="num"></td>
                                    <td><input name="filas[{{ $i }}][descripcion]" placeholder="Nuevo rubro"></td>
                                    <td><input class="u" name="filas[{{ $i }}][unidad]" placeholder="u"></td>
                                    <td><input class="n cant" name="filas[{{ $i }}][cantidad_contratada]"></td>
                                    <td><input class="n precio" name="filas[{{ $i }}][precio_unitario]"></td>
                                    <td class="calc total"></td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            @endif
            <p><button type="submit">Guardar contrato</button></p>
        </form>
        @if ($editarRubros)
            <script>
                document.querySelectorAll('table.hoja tbody tr').forEach((fila) => {
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
                });
            </script>
        @endif
    </div>
@endsection
