<?php

namespace App\Services;

use App\Models\Contrato;
use App\Models\Frente;
use App\Models\Planilla;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PlanillaImporter
{
    public function importar(string $path): Planilla
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(['DATOS', 'PLANILLA LIQ']);
        $book = $reader->load($path);

        return DB::transaction(function () use ($book) {
            $datos = $this->mapa($book->getSheetByName('DATOS'));
            $planillaHoja = $book->getSheetByName('PLANILLA LIQ');

            $contrato = Contrato::create([
                'entidad' => (string) ($datos['B1'] ?? ''),
                'numero_contrato' => $this->texto($datos['B2'] ?? null),
                'codigo_proceso' => (string) ($datos['B3'] ?? ''),
                'objeto' => trim((string) ($datos['B4'] ?? ''), "\" \t"),
                'fecha_suscripcion' => $this->fecha($datos['B5'] ?? null),
                'fecha_inicio' => $this->fecha($datos['B6'] ?? null),
                'fecha_termino' => $this->fecha($datos['B7'] ?? null),
                'ubicacion' => $this->texto($datos['B8'] ?? null),
                'provincia' => $this->texto($datos['B9'] ?? null),
                'contratista' => (string) ($datos['B10'] ?? ''),
                'fiscalizador' => $this->texto($datos['B12'] ?? null),
                'administrador' => $this->texto($datos['B14'] ?? null),
                'plazo' => $this->texto($datos['B16'] ?? null),
                'monto_contrato' => $this->numero($datos['B19'] ?? 0),
                'monto_contrato_iva' => $this->numero($datos['B20'] ?? 0),
                'porcentaje_anticipo' => $this->numero($datos['B21'] ?? 0),
                'anticipo' => $this->numero($datos['B22'] ?? 0),
            ]);

            $cabecera = $this->mapa($planillaHoja, 16);
            $planilla = Planilla::create([
                'contrato_id' => $contrato->id,
                'numero' => (string) ($datos['B25'] ?? $cabecera['M7'] ?? '01'),
                'periodo_desde' => $this->fecha($cabecera['M8'] ?? null),
                'periodo_hasta' => $this->fecha($cabecera['M9'] ?? null),
                'estado' => 'borrador',
                'iva_porcentaje' => 15,
            ]);

            $frente = null;
            $orden = 0;
            $highest = $planillaHoja->getHighestDataRow();

            for ($fila = 17; $fila <= $highest; $fila++) {
                $numero = $this->celda($planillaHoja, "A{$fila}");
                $descripcion = $this->celda($planillaHoja, "C{$fila}");
                $textoA = trim((string) $numero);

                if ($textoA === '' || str_starts_with($textoA, 'APROBADO') || str_contains($textoA, 'MONTO')) {
                    if ($textoA !== '') {
                        break;
                    }
                    continue;
                }

                if (! is_numeric($numero)) {
                    if (strtoupper($textoA) === 'SUBTOTAL' || ! preg_match('/^\d+\./', $textoA)) {
                        continue;
                    }
                    $orden++;
                    $frente = Frente::create([
                        'contrato_id' => $contrato->id,
                        'numero' => $orden,
                        'nombre' => $textoA,
                        'orden' => $orden,
                    ]);
                    continue;
                }

                if ($frente === null || $descripcion === null || $descripcion === '') {
                    continue;
                }

                $rubro = $frente->rubros()->create([
                    'numero' => (int) $numero,
                    'codigo' => $this->texto($this->celda($planillaHoja, "B{$fila}")),
                    'descripcion' => trim((string) $descripcion),
                    'unidad' => (string) $this->celda($planillaHoja, "D{$fila}"),
                    'cantidad_contratada' => $this->numero($this->celda($planillaHoja, "E{$fila}")),
                    'precio_unitario' => $this->numero($this->celda($planillaHoja, "F{$fila}")),
                ]);

                $planilla->ejecuciones()->create([
                    'rubro_id' => $rubro->id,
                    'cantidad_anterior' => $this->numero($this->celda($planillaHoja, "H{$fila}")),
                    'cantidad_actual' => $this->numero($this->celda($planillaHoja, "I{$fila}")),
                ]);
            }

            return $planilla->load('contrato');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function mapa(Worksheet $sheet, int $hasta = 30): array
    {
        $valores = [];
        foreach ($sheet->getRowIterator(1, $hasta) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $valores[$cell->getCoordinate()] = $cell->getValue();
            }
        }

        return $valores;
    }

    private function fecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_numeric($valor)) {
            return Carbon::instance(Date::excelToDateTimeObject((float) $valor))->toDateString();
        }

        return null;
    }

    private function numero(mixed $valor): float
    {
        if ($valor === null || $valor === '') {
            return 0.0;
        }

        return (float) $valor;
    }

    private function celda(Worksheet $sheet, string $coordenada): mixed
    {
        $cell = $sheet->getCell($coordenada);
        if ($cell->isFormula()) {
            return $cell->getOldCalculatedValue();
        }

        return $cell->getValue();
    }

    private function texto(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return trim((string) $valor);
    }
}
