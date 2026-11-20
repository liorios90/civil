<div class="medicion" data-config-panel>
    <p class="medicion-cabeza"><strong>Fórmulas de medición</strong> <span>Opcional. Si no agrega datos ni fórmulas, en mediciones solo aparecerán Descripción y Cantidad para escribir a mano. Si agrega datos (altura, peso…) y fórmulas (por ejemplo =altura*peso), esas columnas se usan en la hoja. La última columna suele ser el total.</span></p>
    <div data-datos>
        @foreach ($medicion as $j => $dato)
            <div class="dato" data-dato>
                <input name="filas[{{ $indice }}][medicion][{{ $j }}][etiqueta]" value="{{ $dato['etiqueta'] ?? '' }}" placeholder="Nombre del dato" maxlength="40">
                <input name="filas[{{ $indice }}][medicion][{{ $j }}][formula]" value="{{ $dato['formula'] ?? '' }}" placeholder="=altura*peso" maxlength="200">
                <button class="danger" type="button" data-quitar-dato>Quitar</button>
            </div>
        @endforeach
    </div>
    <p class="medicion-accion"><button type="button" class="texto" data-agregar-dato>Agregar dato</button></p>
</div>
