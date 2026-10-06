<div class="medicion" data-config-panel>
    <p class="medicion-titulo">Medición de este rubro</p>
    <p class="medicion-ayuda">La descripción siempre está. Agregue los datos que se escriben a mano. Si un dato se calcula, escriba la fórmula con esos nombres, por ejemplo <b>=altura*peso</b>. La última columna es el total que se factura. Si no agrega datos, al planillar se usa la hoja normal.</p>
    <div class="dato dato-fijo">
        <input value="Descripción" disabled>
        <span class="medicion-fijo">Siempre se escribe a mano</span>
    </div>
    <div data-datos>
        @foreach ($medicion as $j => $dato)
            <div class="dato" data-dato>
                <input name="filas[{{ $indice }}][medicion][{{ $j }}][etiqueta]" value="{{ $dato['etiqueta'] ?? '' }}" placeholder="Nombre del dato" maxlength="40">
                <input name="filas[{{ $indice }}][medicion][{{ $j }}][formula]" value="{{ $dato['formula'] ?? '' }}" placeholder="Fórmula, ej. =altura*peso" maxlength="200">
                <button class="danger" type="button" data-quitar-dato>Quitar</button>
            </div>
        @endforeach
    </div>
    <p><button type="button" class="secundario" data-agregar-dato>Agregar dato</button></p>
</div>
