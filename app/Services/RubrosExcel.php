<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RubrosExcel
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function leer(UploadedFile $archivo): array
    {
        $hoja = IOFactory::load($archivo->getRealPath())->getActiveSheet();
        $filas = $hoja->toArray(null, true, true, false);
        if ($filas === []) {
            return [];
        }

        $encabezados = array_map(fn ($valor) => $this->normalizar((string) $valor), $filas[0]);
        $descripcion = $this->columna($encabezados, ['descripcion del rubro', 'descripcion', 'rubro']);
        $unidad = $this->columna($encabezados, ['unidad']);
        $precio = $this->columna($encabezados, ['precio unitario', 'p unitario', 'precio', 'unitario']);
        $cantidad = $this->columna($encabezados, ['cantidad contratada', 'cantidad']);
        $numero = $this->columna($encabezados, ['numero', 'no', 'nro']);
        $conEncabezado = $descripcion !== null;

        if (! $conEncabezado) {
            $descripcion = 0;
            $unidad = 1;
            $precio = 2;
        }

        $resultado = [];
        foreach ($filas as $indice => $fila) {
            if ($conEncabezado && $indice === 0) {
                continue;
            }

            $texto = trim((string) ($fila[$descripcion] ?? ''));
            if ($texto === '' || $this->normalizar($texto) === 'descripcion') {
                continue;
            }

            $item = [
                'descripcion' => $texto,
                'unidad' => mb_substr(trim((string) ($fila[$unidad] ?? '')), 0, 20),
                'precio_unitario' => $this->numero($fila[$precio] ?? 0),
                'cantidad_contratada' => $cantidad === null ? 0 : $this->numero($fila[$cantidad] ?? 0),
            ];
            if ($numero !== null && (int) ($fila[$numero] ?? 0) > 0) {
                $item['numero'] = (int) $fila[$numero];
            }
            $resultado[] = $item;
        }

        return $resultado;
    }

    /**
     * @param  array<int, string>  $encabezados
     * @param  array<int, string>  $nombres
     */
    private function columna(array $encabezados, array $nombres): ?int
    {
        foreach ($encabezados as $indice => $encabezado) {
            if (in_array($encabezado, $nombres, true)) {
                return $indice;
            }
        }

        return null;
    }

    private function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', '°' => '']);
        $texto = preg_replace('/[^a-z0-9]+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    private function numero(mixed $valor): float
    {
        if (is_int($valor) || is_float($valor)) {
            return round((float) $valor, 2);
        }

        $texto = trim(str_replace(['$', ' '], '', (string) $valor));
        if ($texto === '') {
            return 0;
        }

        $coma = strrpos($texto, ',');
        $punto = strrpos($texto, '.');
        if ($coma !== false && $punto !== false) {
            $texto = $coma > $punto
                ? str_replace(',', '.', str_replace('.', '', $texto))
                : str_replace(',', '', $texto);
        } elseif ($coma !== false) {
            $texto = str_replace(',', '.', $texto);
        }

        return round((float) $texto, 2);
    }
}
