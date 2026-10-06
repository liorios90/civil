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
