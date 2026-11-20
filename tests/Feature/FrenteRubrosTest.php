<?php

namespace Tests\Feature;

use App\Models\Contrato;
use App\Models\Empresa;
use App\Models\PlanillaRubro;
use App\Services\FrenteRubros;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrenteRubrosTest extends TestCase
{
    use RefreshDatabase;

    public function test_actualizar_rubros_los_pone_en_la_ultima_planilla_para_mediciones(): void
    {
        $empresa = Empresa::query()->create([
            'nombre' => 'Obra Demo',
            'responsable' => 'Admin',
            'fecha_inicio' => now()->toDateString(),
            'activo' => true,
        ]);
        $contrato = Contrato::query()->create([
            'empresa_id' => $empresa->id,
            'entidad' => 'Municipio',
            'codigo_proceso' => 'PROC-1',
            'objeto' => 'Obra',
            'contratista' => 'Constructor',
            'monto_contrato' => 1000,
            'monto_contrato_iva' => 1150,
            'iva_porcentaje' => 15,
            'porcentaje_anticipo' => 0,
            'anticipo' => 0,
        ]);
        $contrato->planillas()->create([
            'numero' => '01',
            'estado' => 'borrador',
            'iva_porcentaje' => 15,
        ]);
        $frente = $contrato->frentes()->create([
            'numero' => 1,
            'nombre' => 'planilla 1',
            'orden' => 1,
            'es_catalogo' => false,
        ]);

        app(FrenteRubros::class)->aplicarCatalogo($contrato, [
            [
                'descripcion' => 'Excavación',
                'unidad' => 'm3',
                'cantidad_contratada' => 10,
                'precio_unitario' => 5,
            ],
            [
                'descripcion' => 'Sin cantidad',
                'unidad' => 'u',
                'cantidad_contratada' => 0,
                'precio_unitario' => 1,
            ],
        ], true, true);

        $frente->refresh()->load('rubros');
        $this->assertCount(1, $frente->rubros);
        $this->assertSame('Excavación', $frente->rubros->first()->descripcion);
        $this->assertSame('m3', $frente->rubros->first()->unidad);

        $ejecucion = PlanillaRubro::query()->where('rubro_id', $frente->rubros->first()->id)->first();
        $this->assertNotNull($ejecucion);
    }
}
