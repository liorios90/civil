<?php

namespace App\Console\Commands;

use App\Services\PlanillaImporter;
use Illuminate\Console\Command;

class ImportarPlanilla extends Command
{
    protected $signature = 'planilla:importar {archivo}';

    protected $description = 'Importa la hoja DATOS y PLANILLA LIQ de un Excel de liquidación';

    public function handle(PlanillaImporter $importer): int
    {
        $archivo = $this->argument('archivo');
        if (! is_file($archivo)) {
            $this->error("No se encontró el archivo: {$archivo}");

            return self::FAILURE;
        }

        $planilla = $importer->importar($archivo);
        $this->info("Contrato {$planilla->contrato->codigo_proceso} importado. Planilla {$planilla->numero} (#{$planilla->id}).");

        return self::SUCCESS;
    }
}
