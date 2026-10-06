<div class="medicion" data-config-panel>
    <p class="medicion-cabeza"><strong>Medición</strong> <span>La descripción siempre está. En la fórmula use los nombres, por ejemplo =altura*peso. La última columna es el total. Esta medición se copia al usarla en un contrato; cambiarla después no modifica los contratos ya hechos.</span></p>
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
