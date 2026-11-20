<?php

namespace App\Services;

class UnidadMedicion
{
    public const LONGITUD = 'longitud';

    public const AREA = 'area';

    public const VOLUMEN = 'volumen';

    public const KILOGRAMO = 'kg';

    public const M3KM = 'm3km';

    public const NUMERO = 'numero';

    public static function tipo(?string $unidad): string
    {
        $texto = mb_strtolower(trim((string) $unidad));
        $texto = str_replace(['²', '³'], ['2', '3'], $texto);
        $texto = preg_replace('/\s+/', '', $texto) ?? $texto;
        $texto = str_replace(['/', '_'], '-', $texto);
        $texto = str_replace('.', '', $texto);

        if (in_array($texto, ['m3-km', 'm3km'], true)) {
            return self::M3KM;
        }
        if (in_array($texto, ['kg', 'kgs', 'kilogramo', 'kilogramos'], true)) {
            return self::KILOGRAMO;
        }
        if ($texto === 'm3') {
            return self::VOLUMEN;
        }
        if ($texto === 'm2') {
            return self::AREA;
        }
        if (in_array($texto, ['m', 'ml'], true)) {
            return self::LONGITUD;
        }

        return self::NUMERO;
    }

    public static function explica(string $tipo): string
    {
        return match ($tipo) {
            self::LONGITUD => 'Unidad de longitud (m). Complete Base 1 y Número; Longitud y Total se calculan solos.',
            self::AREA => 'Unidad de área (m²). Complete Base 1, Base 2 o Altura y Número; Área y Total se calculan solos.',
            self::VOLUMEN => 'Unidad de volumen (m³). Complete Base 1, Base 2, Altura y Número; Volumen y Total se calculan solos.',
            self::KILOGRAMO => 'Unidad en kilogramos. Escriba el peso en Número; el Total se completa solo.',
            self::M3KM => 'Unidad m³·km. Complete las bases, Altura y Km; Volumen × Km da el Total.',
            default => 'Unidad por cantidad. Escriba el valor en Número; el Total se completa solo.',
        };
    }

    /**
     * Fórmulas por defecto según la unidad (para mostrar y editar en la hoja).
     *
     * @return array<string, string>
     */
    public static function formulasPorDefecto(string $tipo): array
    {
        return match ($tipo) {
            self::LONGITUD => [
                'longitud' => '=base1*numero',
                'total' => '=longitud',
            ],
            self::AREA => [
                'area' => '=base1*base2*numero',
                'total' => '=area',
            ],
            self::VOLUMEN => [
                'volumen' => '=base1*base2*altura*numero',
                'total' => '=volumen',
            ],
            self::M3KM => [
                'volumen' => '=base1*base2*altura',
                'total' => '=volumen*numero',
            ],
            default => [
                'total' => '=numero',
            ],
        };
    }

    /**
     * Resumen legible de las fórmulas activas.
     *
     * @return list<array{titulo: string, texto: string}>
     */
    public static function resumenFormulas(string $tipo): array
    {
        return match ($tipo) {
            self::LONGITUD => [
                ['titulo' => 'Longitud', 'texto' => 'Base 1 × Número'],
                ['titulo' => 'Total', 'texto' => 'Longitud'],
            ],
            self::AREA => [
                ['titulo' => 'Área', 'texto' => 'Base 1 × Base 2 × Número (o Base 1 × Altura × Número)'],
                ['titulo' => 'Total', 'texto' => 'Área'],
            ],
            self::VOLUMEN => [
                ['titulo' => 'Volumen', 'texto' => 'Base 1 × Base 2 × Altura × Número'],
                ['titulo' => 'Total', 'texto' => 'Volumen'],
            ],
            self::M3KM => [
                ['titulo' => 'Volumen', 'texto' => 'Base 1 × Base 2 × Altura'],
                ['titulo' => 'Total', 'texto' => 'Volumen × Km'],
            ],
            default => [
                ['titulo' => 'Total', 'texto' => 'Número'],
            ],
        };
    }

    public static function marca(string $tipo, string $campo): bool
    {
        return match ($tipo) {
            self::LONGITUD => $campo === 'longitud',
            self::AREA => $campo === 'area',
            self::VOLUMEN => $campo === 'volumen',
            self::M3KM => $campo === 'volumen' || $campo === 'numero',
            default => $campo === 'numero' || $campo === 'total',
        };
    }

    /**
     * @param  array<string, mixed>  $linea
     * @return array{longitud: ?float, area: ?float, volumen: ?float}
     */
    public static function desdeDimensiones(array $linea, string $tipo): array
    {
        $base1 = self::numero($linea['base1'] ?? null);
        $base2 = self::numero($linea['base2'] ?? null);
        $altura = self::numero($linea['altura'] ?? null);
        $numero = self::numero($linea['numero'] ?? null);
        $factor = $tipo === self::M3KM || $tipo === self::KILOGRAMO ? 1.0 : ($numero ?? 1.0);

        $longitud = $base1 === null ? null : round($base1 * $factor, 2);
        $area = null;
        if ($base1 !== null && $base2 !== null) {
            $area = round($base1 * $base2 * $factor, 2);
        } elseif ($base1 !== null && $altura !== null) {
            $area = round($base1 * $altura * $factor, 2);
        }
        $volumen = ($base1 !== null && $base2 !== null && $altura !== null)
            ? round($base1 * $base2 * $altura * $factor, 2)
            : null;

        return [
            'longitud' => $longitud,
            'area' => $area,
            'volumen' => $volumen,
        ];
    }

    public static function total(string $tipo, ?float $longitud, ?float $area, ?float $volumen, ?float $numero): ?float
    {
        $valor = match ($tipo) {
            self::LONGITUD => $longitud,
            self::AREA => $area,
            self::VOLUMEN => $volumen,
            self::M3KM => ($volumen === null || $numero === null) ? null : round($volumen * $numero, 2),
            default => $numero,
        };

        return $valor === null ? null : round($valor, 2);
    }

    public static function numero(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return (float) $valor;
    }
}
