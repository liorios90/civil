<?php

namespace Tests\Unit;

use App\Models\Rubro;
use App\Services\PlanillaCalculator;
use Tests\TestCase;

class PlanillaCalculatorTest extends TestCase
{
    public function test_calcula_valor_redondeado_e_incremento(): void
    {
        $rubro = new Rubro([
            'cantidad_contratada' => 74,
            'precio_unitario' => 11.15,
        ]);

        $linea = (new PlanillaCalculator)->linea($rubro, 164.68, 8.58);

        $this->assertEquals(95.67, $linea['valor_actual']);
        $this->assertEquals(1836.18, $linea['valor_anterior']);
        $this->assertSame('INCREMENTO DE CANTIDADES', $linea['observacion']);
        $this->assertEqualsWithDelta(99.26, $linea['incremento_cantidad'], 0.001);
    }
}
