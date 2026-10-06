<?php

namespace Tests\Unit;

use App\Services\HojaCalculo;
use App\Services\UnidadMedicion;
use Tests\TestCase;

class HojaCalculoTest extends TestCase
{
    public function test_calcula_formulas_y_mantiene_el_total_automatico(): void
    {
        $columnas = HojaCalculo::columnasIniciales(UnidadMedicion::AREA);
        $filas = [
            $this->fila(['base1' => '3', 'base2' => '4', 'numero' => '2']),
            $this->fila(['base1' => '1', 'numero' => '5', 'total' => '=I1*2']),
            $this->fila(['base1' => '10', 'longitud' => '9', 'numero' => '1']),
        ];

        $res = HojaCalculo::resolver($filas, $columnas, UnidadMedicion::AREA);

        $this->assertEquals(6, $res[0]['numeros']['longitud']);
        $this->assertEquals(24, $res[0]['numeros']['area']);
        $this->assertEquals(24, $res[0]['numeros']['total']);
        $this->assertEquals(48, $res[1]['numeros']['total']);
        $this->assertEquals(9, $res[2]['numeros']['longitud']);
        $this->assertSame('=I1*2', $res[1]['celdas']['total']);
    }

    public function test_la_plantilla_de_medicion_usa_nombres_y_descripcion(): void
    {
        $columnas = HojaCalculo::columnasDesdeMedicion([
            ['etiqueta' => 'altura', 'formula' => ''],
            ['etiqueta' => 'peso', 'formula' => ''],
            ['etiqueta' => 'Prof. promedio', 'formula' => '=(C1+D1+E1)/3'],
            ['etiqueta' => '=altura*peso', 'formula' => ''],
        ]);

        $this->assertSame('descripcion', $columnas[0]['clave']);
        $this->assertSame('Descripción', $columnas[0]['etiqueta']);
        $this->assertSame('total', $columnas[4]['clave']);
        $this->assertSame('Total', $columnas[4]['etiqueta']);
        $this->assertSame('=altura*peso', $columnas[4]['formula']);
        $this->assertSame('=(C2+D2+E2)/3', HojaCalculo::formulaEnFila($columnas[3]['formula'], 2));
        $this->assertSame([], HojaCalculo::columnasDesdeMedicion([['etiqueta' => '  ', 'formula' => '']]));

        $filas = [[
            'descripcion' => 'tramo 1',
            'e0' => '2',
            'e1' => '4',
            'e2' => '',
            'total' => '=altura*peso',
        ]];
        $res = HojaCalculo::resolver($filas, $columnas, UnidadMedicion::NUMERO);

        $this->assertSame('tramo 1', $res[0]['descripcion']);
        $this->assertEquals(8, $res[0]['numeros']['total']);

        $trapecio = HojaCalculo::columnasDesdeMedicion([
            ['etiqueta' => 'Largo', 'formula' => ''],
            ['etiqueta' => 'Ancho', 'formula' => ''],
            ['etiqueta' => 'Prof. izq', 'formula' => ''],
            ['etiqueta' => 'Prof. der', 'formula' => ''],
            ['etiqueta' => 'Total', 'formula' => '=largo*ancho*((prof. izq+prof. der)/2)'],
        ]);
        $corte = HojaCalculo::resolver([[
            'descripcion' => 'corte',
            'e0' => '10',
            'e1' => '2',
            'e2' => '1',
            'e3' => '3',
            'total' => '=largo*ancho*((prof. izq+prof. der)/2)',
        ]], $trapecio, UnidadMedicion::NUMERO);

        $this->assertEquals(40, $corte[0]['numeros']['total']);
    }

    public function test_la_formula_de_la_columna_se_guarda_y_completa_las_filas(): void
    {
        $columnas = HojaCalculo::normalizar(
            ['descripcion', 'e0', 'total'],
            ['descripcion' => 'Descripción', 'e0' => 'altura', 'total' => 'Total'],
            UnidadMedicion::NUMERO,
            ['total' => 'altura*peso'],
        );

        $this->assertSame('=altura*peso', $columnas[2]['formula']);
        $this->assertSame('=altura*peso', HojaCalculo::columnas($columnas, UnidadMedicion::NUMERO)[2]['formula']);

        $sinFormula = [
            ['clave' => 'descripcion', 'etiqueta' => 'Descripción'],
            ['clave' => 'e0', 'etiqueta' => 'altura'],
            ['clave' => 'total', 'etiqueta' => 'Total'],
        ];
        $plantilla = HojaCalculo::columnasDesdeMedicion([
            ['etiqueta' => 'altura', 'formula' => ''],
            ['etiqueta' => 'Total', 'formula' => '=altura*peso'],
        ]);
        $conPlantilla = HojaCalculo::conFormulas($sinFormula, $plantilla, []);
        $this->assertSame('=altura*peso', $conPlantilla[2]['formula']);

        $personalizada = HojaCalculo::conFormulas([
            ['clave' => 'total', 'etiqueta' => 'Total', 'formula' => '=altura*2'],
        ], $plantilla, []);
        $this->assertSame('=altura*2', $personalizada[0]['formula']);

        $desdeFilas = HojaCalculo::conFormulas($sinFormula, [], [
            ['celdas' => ['total' => '=B2*C2', 'e0' => '4']],
            ['celdas' => ['total' => '=B5*C5']],
        ]);
        $this->assertSame('=B1*C1', $desdeFilas[2]['formula']);
        $this->assertSame('=B3*C3', HojaCalculo::formulaEnFila($desdeFilas[2]['formula'], 3));
    }

    public function test_la_medicion_del_rubro_no_consulta_el_catalogo_general(): void
    {
        $rubro = new \App\Models\Rubro([
            'descripcion' => 'Excavación',
            'medicion' => [
                ['etiqueta' => 'altura', 'formula' => ''],
                ['etiqueta' => 'Total', 'formula' => '=altura*2'],
            ],
        ]);

        $columnas = HojaCalculo::plantillaPara($rubro);

        $this->assertSame('descripcion', $columnas[0]['clave']);
        $this->assertSame('=altura*2', $columnas[2]['formula']);
        $this->assertSame([], HojaCalculo::plantillaPara(new \App\Models\Rubro(['descripcion' => 'Excavación'])));
    }

    public function test_acepta_suma_promedio_y_referencia_circular(): void
    {
        $columnas = [
            ['clave' => 'a', 'etiqueta' => 'A'],
            ['clave' => 'b', 'etiqueta' => 'B'],
            ['clave' => 'total', 'etiqueta' => 'Total'],
        ];
        $filas = [
            ['a' => '1', 'b' => '=A1+1', 'total' => '=SUMA(A1:A3)'],
            ['a' => '2', 'b' => '=PROMEDIO(A1:A2)', 'total' => '=REDONDEAR(10/3,2)'],
            ['a' => '3', 'b' => '=A3', 'total' => '=B3'],
            ['a' => '=B4', 'b' => '=A4', 'total' => ''],
        ];

        $res = HojaCalculo::resolver($filas, $columnas, UnidadMedicion::NUMERO);

        $this->assertEquals(2, $res[0]['numeros']['b']);
        $this->assertEquals(6, $res[0]['numeros']['total']);
        $this->assertEquals(1.5, $res[1]['numeros']['b']);
        $this->assertEquals(3.33, $res[1]['numeros']['total']);
        $this->assertEquals(3, $res[2]['numeros']['total']);
        $this->assertNull($res[3]['numeros']['a']);
        $this->assertNull($res[3]['numeros']['b']);
    }

    /**
     * @param  array<string, string>  $valores
     * @return array<string, string>
     */
    private function fila(array $valores): array
    {
        $fila = [];
        foreach (HojaCalculo::columnasIniciales(UnidadMedicion::AREA) as $columna) {
            $fila[$columna['clave']] = $valores[$columna['clave']] ?? '';
        }

        return $fila;
    }
}
