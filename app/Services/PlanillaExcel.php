<?php

namespace App\Services;

use App\Models\Planilla;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlanillaExcel
{
    /**
     * @param  array<string, mixed>  $liquidacion
     */
    public function descargar(Planilla $planilla, array $liquidacion): StreamedResponse
    {
        $libro = $this->libro($planilla, $liquidacion);
        $codigo = preg_replace('/[^\w\-]+/u', '-', (string) $planilla->contrato->codigo_proceso) ?: 'planilla';
        $nombre = 'planilla-'.$codigo.'-'.$planilla->numero.'.xlsx';

        return response()->streamDownload(function () use ($libro) {
            (new Xlsx($libro))->save('php://output');
        }, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<string, mixed>  $liquidacion
     */
    public function libro(Planilla $planilla, array $liquidacion): Spreadsheet
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Planilla');
        $contrato = $planilla->contrato;

        $this->titulo($hoja, 1, (string) $contrato->entidad, 14);
        $this->titulo($hoja, 2, (string) $contrato->objeto, 12);
        $hoja->getRowDimension(2)->setRowHeight(32);
        $this->titulo($hoja, 3, 'PLANILLA DE OBRAS EJECUTADAS', 12);

        $desde = optional($planilla->periodo_desde)->format('d/m/Y');
        $hasta = optional($planilla->periodo_hasta)->format('d/m/Y');
        $periodo = ($desde || $hasta) ? trim($desde.' al '.$hasta) : '';
        $this->meta($hoja, 6, 'Objeto:', $contrato->objeto, 'Planilla N°', $planilla->numero);
        $this->meta($hoja, 7, 'Código:', $contrato->codigo_proceso, 'Período:', $periodo);
        $this->meta($hoja, 8, 'Contratista:', $contrato->contratista, 'Monto contratado:', $liquidacion['totales']['contratado']);
        $this->meta($hoja, 9, 'Fiscalizador:', $contrato->fiscalizador, 'Monto de planilla:', $liquidacion['totales']['actual']);
        $this->meta($hoja, 10, 'Administrador:', $contrato->administrador, 'Ejecutado acumulado:', $liquidacion['totales']['acumulado']);
        $this->dinero($hoja, 'N8:N10');

        $fila = 12;
        $hoja->setCellValue("A{$fila}", 'No.');
        $hoja->setCellValue("B{$fila}", 'Descripción del rubro');
        $hoja->setCellValue("C{$fila}", 'U');
        $hoja->setCellValue("D{$fila}", 'Contratado');
        $hoja->setCellValue("G{$fila}", 'Cantidades ejecutadas');
        $hoja->setCellValue("J{$fila}", 'Total en dólares');
        $hoja->setCellValue("M{$fila}", 'Incrementos');
        $hoja->setCellValue("O{$fila}", 'Decrementos');
        $hoja->setCellValue("Q{$fila}", '%');
        $hoja->setCellValue("R{$fila}", 'Observaciones');
        foreach (['A', 'B', 'C', 'Q', 'R'] as $columna) {
            $hoja->mergeCells($columna.$fila.':'.$columna.($fila + 1));
        }
        $hoja->mergeCells("D{$fila}:F{$fila}");
        $hoja->mergeCells("G{$fila}:I{$fila}");
        $hoja->mergeCells("J{$fila}:L{$fila}");
        $hoja->mergeCells("M{$fila}:N{$fila}");
        $hoja->mergeCells("O{$fila}:P{$fila}");

        $sub = $fila + 1;
        foreach ([
            'D' => 'Cant.', 'E' => 'Unit.', 'F' => 'Total',
            'G' => 'Ant.', 'H' => 'Actual', 'I' => 'Total',
            'J' => 'Ant.', 'K' => 'Actual', 'L' => 'Total',
            'M' => 'Cant.', 'N' => 'P. total',
            'O' => 'Cant.', 'P' => 'P. total',
            'Q' => '%',
        ] as $columna => $texto) {
            $hoja->setCellValue($columna.$sub, $texto);
        }
        $this->pintar($hoja, "A{$fila}:R{$sub}", 'D9E2F0', true, Alignment::HORIZONTAL_CENTER);

        $fila = $sub + 1;
        $subtotales = [];
        foreach ($liquidacion['frentes'] as $grupo) {
            $hoja->mergeCells("A{$fila}:R{$fila}");
            $hoja->setCellValue("A{$fila}", $grupo['frente']->nombre);
            $this->pintar($hoja, "A{$fila}:R{$fila}", '1F4E79', true, Alignment::HORIZONTAL_LEFT, 'FFFFFF');
            $fila++;

            $inicio = $fila;
            foreach ($grupo['lineas'] as $linea) {
                $k = $linea['calculo'];
                $r = $fila;
                $hoja->setCellValue("A{$r}", $linea['rubro']->numero);
                $hoja->setCellValue("B{$r}", $linea['rubro']->descripcion);
                $hoja->setCellValue("C{$r}", $linea['rubro']->unidad);
                $hoja->setCellValue("D{$r}", $this->numero($k['cantidad_contratada']));
                $hoja->setCellValue("E{$r}", $this->numero($k['precio_unitario']));
                $hoja->setCellValue("G{$r}", $this->numero($k['cantidad_anterior']));
                $hoja->setCellValue("H{$r}", $this->numero($k['cantidad_actual']));
                $hoja->setCellValue("F{$r}", "=ROUND(D{$r}*E{$r},2)");
                $hoja->setCellValue("I{$r}", "=ROUND(G{$r}+H{$r},2)");
                $hoja->setCellValue("J{$r}", "=ROUND(G{$r}*E{$r},2)");
                $hoja->setCellValue("K{$r}", "=ROUND(H{$r}*E{$r},2)");
                $hoja->setCellValue("L{$r}", "=ROUND(J{$r}+K{$r},2)");
                $hoja->setCellValue("M{$r}", "=IF(I{$r}>D{$r},ROUND(I{$r}-D{$r},2),\"\")");
                $hoja->setCellValue("N{$r}", "=IF(M{$r}=\"\",\"\",ROUND(M{$r}*E{$r},2))");
                $hoja->setCellValue("O{$r}", "=IF(I{$r}<D{$r},ROUND(I{$r}-D{$r},2),\"\")");
                $hoja->setCellValue("P{$r}", "=IF(O{$r}=\"\",\"\",ROUND(O{$r}*E{$r},2))");
                $hoja->setCellValue("Q{$r}", "=IF(F{$r}=0,\"\",ROUND(L{$r}/F{$r}*100,2))");
                $hoja->setCellValue("R{$r}", "=IF(I{$r}>D{$r},\"INCREMENTO DE CANTIDADES\",\"\")");
                $this->dinero($hoja, "D{$r}:Q{$r}");
                $fila++;
            }

            $fin = $fila - 1;
            $suma = fn (string $columna) => $fin >= $inicio ? "=SUM({$columna}{$inicio}:{$columna}{$fin})" : '=0';
            $hoja->mergeCells("A{$fila}:E{$fila}");
            $hoja->setCellValue("A{$fila}", 'SUBTOTAL');
            $hoja->setCellValue("F{$fila}", $suma('F'));
            $hoja->setCellValue("J{$fila}", $suma('J'));
            $hoja->setCellValue("K{$fila}", $suma('K'));
            $hoja->setCellValue("L{$fila}", $suma('L'));
            $this->dinero($hoja, "F{$fila}:L{$fila}");
            $this->pintar($hoja, "A{$fila}:R{$fila}", 'E2EFDA', true);
            $subtotales[] = $fila;
            $fila++;
        }

        $sumar = function (string $columna) use ($subtotales): string {
            if ($subtotales === []) {
                return '=0';
            }

            return '='.implode('+', array_map(fn (int $subtotal) => $columna.$subtotal, $subtotales));
        };
        $trabajos = $fila;
        $this->cierre($hoja, $trabajos, 'TRABAJOS REALIZADOS', [
            'anterior' => $sumar('J'),
            'actual' => $sumar('K'),
            'acumulado' => $sumar('L'),
        ], $sumar('F'), "=CONCATENATE(\"Saldo \",TEXT(F{$trabajos}-L{$trabajos},\"#,##0.00\"))");
        $hoja->setCellValue('N8', "=F{$trabajos}");
        $hoja->setCellValue('N9', "=K{$trabajos}");
        $hoja->setCellValue('N10', "=L{$trabajos}");
        $fila++;
        $iva = (float) $planilla->iva_porcentaje;
        $this->cierre($hoja, $fila, 'IVA '.$planilla->iva_porcentaje.' %', [
            'anterior' => "=ROUND(J{$trabajos}*{$iva}/100,2)",
            'actual' => "=ROUND(K{$trabajos}*{$iva}/100,2)",
            'acumulado' => "=ROUND(L{$trabajos}*{$iva}/100,2)",
        ]);
        $fila++;
        $anticipo = (float) $liquidacion['porcentaje_anticipo'];
        $amortizacion = $fila;
        $this->cierre($hoja, $amortizacion, 'AMORTIZACIÓN ANTICIPO '.number_format($anticipo * 100, 0).' %', [
            'anterior' => "=ROUND(J{$trabajos}*{$anticipo},2)",
            'actual' => "=ROUND(K{$trabajos}*{$anticipo},2)",
            'acumulado' => "=ROUND(L{$trabajos}*{$anticipo},2)",
        ]);
        $fila++;
        $descuentos = (float) $planilla->descuentos;
        $multas = (float) $planilla->multas;
        $this->cierre($hoja, $fila, 'LÍQUIDO A PAGAR', [
            'anterior' => "=ROUND(J{$trabajos}-J{$amortizacion}-{$descuentos}-{$multas},2)",
            'actual' => "=ROUND(K{$trabajos}-K{$amortizacion}-{$descuentos}-{$multas},2)",
            'acumulado' => "=ROUND(L{$trabajos}-L{$amortizacion}-{$descuentos}-{$multas},2)",
        ]);

        $fila += 3;
        $hoja->setCellValue("A{$fila}", $contrato->administrador);
        $hoja->setCellValue("G{$fila}", $contrato->fiscalizador);
        $hoja->setCellValue("M{$fila}", $contrato->contratista);
        $fila++;
        $hoja->setCellValue("A{$fila}", 'ADMINISTRADOR DE CONTRATO');
        $hoja->setCellValue("G{$fila}", 'FISCALIZADOR');
        $hoja->setCellValue("M{$fila}", 'CONTRATISTA');
        $hoja->getStyle("A{$fila}:M{$fila}")->getFont()->setBold(true);

        $hoja->getStyle('A6:R'.$fila)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $hoja->getStyle('B:B')->getAlignment()->setWrapText(true);
        $hoja->getStyle('A:R')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach ([
            'A' => 6, 'B' => 42, 'C' => 8, 'D' => 12, 'E' => 12, 'F' => 14,
            'G' => 12, 'H' => 12, 'I' => 12, 'J' => 14, 'K' => 14, 'L' => 14,
            'M' => 12, 'N' => 14, 'O' => 12, 'P' => 14, 'Q' => 8, 'R' => 24,
        ] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }

        $ajuste = $hoja->getPageSetup();
        $ajuste->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $ajuste->setPaperSize(PageSetup::PAPERSIZE_A4);
        $ajuste->setFitToWidth(1);
        $ajuste->setFitToHeight(0);
        $ajuste->setScale(null, false);
        $ajuste->setHorizontalCentered(true);
        $ajuste->setPrintArea('A1:R'.$fila);
        $hoja->getPageMargins()
            ->setTop(0.4)
            ->setBottom(0.4)
            ->setLeft(0.25)
            ->setRight(0.25)
            ->setHeader(0.2)
            ->setFooter(0.2);
        $hoja->getSheetView()->setView(SheetView::SHEETVIEW_PAGE_LAYOUT);
        $hoja->freezePane('A14');

        return $libro;
    }

    private function titulo(Worksheet $hoja, int $fila, string $texto, int $tamano = 12): void
    {
        $hoja->mergeCells("A{$fila}:R{$fila}");
        $hoja->setCellValue("A{$fila}", $texto);
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true)->setSize($tamano);
        $hoja->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
    }

    private function meta(Worksheet $hoja, int $fila, string $etiqueta, mixed $valor, string $etiquetaDerecha, mixed $valorDerecho): void
    {
        $hoja->setCellValue("A{$fila}", $etiqueta);
        $hoja->mergeCells("B{$fila}:L{$fila}");
        $hoja->setCellValue("B{$fila}", $valor);
        $hoja->setCellValue("M{$fila}", $etiquetaDerecha);
        $hoja->mergeCells("N{$fila}:R{$fila}");
        $hoja->setCellValue("N{$fila}", is_numeric($valorDerecho) ? $this->numero($valorDerecho) : $valorDerecho);
        $hoja->getStyle("A{$fila}")->getFont()->setBold(true);
        $hoja->getStyle("M{$fila}")->getFont()->setBold(true);
    }

    /**
     * @param  array<string, float>  $valores
     */
    private function cierre(Worksheet $hoja, int $fila, string $texto, array $valores, mixed $contratado = null, string $nota = ''): void
    {
        if ($contratado === null) {
            $hoja->mergeCells("A{$fila}:I{$fila}");
        } else {
            $hoja->mergeCells("A{$fila}:E{$fila}");
            $hoja->setCellValue("F{$fila}", $this->formulaONumero($contratado));
        }
        $hoja->setCellValue("A{$fila}", $texto);
        $hoja->setCellValue("J{$fila}", $this->formulaONumero($valores['anterior']));
        $hoja->setCellValue("K{$fila}", $this->formulaONumero($valores['actual']));
        $hoja->setCellValue("L{$fila}", $this->formulaONumero($valores['acumulado']));
        if ($nota !== '') {
            $hoja->mergeCells("M{$fila}:R{$fila}");
            $hoja->setCellValue("M{$fila}", $this->formulaONumero($nota));
        }
        $this->dinero($hoja, "J{$fila}:L{$fila}");
        $this->pintar($hoja, "A{$fila}:R{$fila}", 'FFF2CC', true);
    }

    private function pintar(Worksheet $hoja, string $rango, string $fondo, bool $negrita = false, string $horizontal = Alignment::HORIZONTAL_LEFT, string $color = '000000'): void
    {
        $estilo = $hoja->getStyle($rango);
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fondo);
        $estilo->getFont()->setBold($negrita)->getColor()->setRGB($color);
        $estilo->getAlignment()->setHorizontal($horizontal)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
    }

    private function dinero(Worksheet $hoja, string $rango): void
    {
        $hoja->getStyle($rango)->getNumberFormat()->setFormatCode('#,##0.00');
        $hoja->getStyle($rango)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private function formulaONumero(mixed $valor): mixed
    {
        if (is_string($valor) && (str_starts_with($valor, '=') || ! is_numeric($valor))) {
            return $valor;
        }

        return $this->numero($valor);
    }

    private function numero(mixed $valor): float|string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        return round((float) $valor, 2);
    }

    private function texto(mixed $valor): string
    {
        return number_format((float) $valor, 2);
    }
}
