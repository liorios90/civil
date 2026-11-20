<?php

namespace Tests\Unit;

use App\Services\RubrosExcel;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RubrosExcelTest extends TestCase
{
    public function test_sin_columna_cantidad_asume_uno_para_entrar_a_mediciones(): void
    {
        $ruta = storage_path('app/testing-rubros.xlsx');
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->fromArray([
            ['Descripción', 'Unidad', 'Precio unitario'],
            ['Relleno', 'm2', '12.5'],
        ]);
        (new Xlsx($libro))->save($ruta);

        try {
            $filas = app(RubrosExcel::class)->leer(new UploadedFile($ruta, 'rubros.xlsx', null, null, true));
            $this->assertCount(1, $filas);
            $this->assertSame('Relleno', $filas[0]['descripcion']);
            $this->assertSame('m2', $filas[0]['unidad']);
            $this->assertSame(1.0, $filas[0]['cantidad_contratada']);
            $this->assertSame(12.5, $filas[0]['precio_unitario']);
        } finally {
            @unlink($ruta);
        }
    }
}
