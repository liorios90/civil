<?php

namespace App\Services;

use Carbon\CarbonInterface;

class Historial
{
    public const ZONA = 'America/Guayaquil';

    private static int $silencio = 0;

    /**
     * Ejecuta un bloque sin anotar cada fila en el historial (por ejemplo, al copiar rubros a una planilla nueva).
     * Las columnas creado_por y modificado_por se siguen llenando.
     *
     * @template T
     *
     * @param  callable(): T  $bloque
     * @return T
     */
    public static function sinRegistro(callable $bloque): mixed
    {
        self::$silencio++;
        try {
            return $bloque();
        } finally {
            self::$silencio--;
        }
    }

    public static function silenciado(): bool
    {
        return self::$silencio > 0;
    }

    public static function fecha(?CarbonInterface $fecha): string
    {
        return $fecha ? $fecha->copy()->timezone(self::ZONA)->format('d/m/Y H:i') : '';
    }

    public static function campo(string $campo): string
    {
        return [
            'entidad' => 'Entidad',
            'numero_contrato' => 'Número de contrato',
            'codigo_proceso' => 'Código del proceso',
            'objeto' => 'Objeto',
            'fecha_suscripcion' => 'Fecha de suscripción',
            'fecha_inicio' => 'Inicio',
            'fecha_termino' => 'Término',
            'ubicacion' => 'Ubicación',
            'provincia' => 'Provincia',
            'contratista' => 'Contratista',
            'fiscalizador' => 'Fiscalizador',
            'administrador' => 'Administrador',
            'plazo' => 'Plazo',
            'monto_contrato' => 'Monto sin IVA',
            'monto_contrato_iva' => 'Monto con IVA',
            'iva_porcentaje' => 'IVA %',
            'porcentaje_anticipo' => 'Anticipo %',
            'anticipo' => 'Anticipo',
            'nombre' => 'Nombre',
            'numero' => 'Número',
            'descripcion' => 'Descripción',
            'unidad' => 'Unidad',
            'cantidad_contratada' => 'Cantidad contratada',
            'precio_unitario' => 'Precio unitario',
            'cantidad_anterior' => 'Cantidad anterior',
            'cantidad_actual' => 'Cantidad actual',
            'base1' => 'Base 1',
            'base2' => 'Base 2',
            'altura' => 'Altura',
            'longitud' => 'Longitud',
            'area' => 'Área',
            'volumen' => 'Volumen',
            'total' => 'Total',
            'ruta' => 'Archivo',
            'periodo_desde' => 'Período desde',
            'periodo_hasta' => 'Período hasta',
            'estado' => 'Estado',
            'descuentos' => 'Descuentos',
            'multas' => 'Multas',
        ][$campo] ?? $campo;
    }

    public static function valor(mixed $valor, string $campo = ''): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }
        if ($campo === 'numero' && is_numeric($valor) && fmod((float) $valor, 1.0) === 0.0) {
            return (string) (int) $valor;
        }
        if (is_numeric($valor)) {
            return number_format(round((float) $valor, 2), 2, '.', ',');
        }
        if (is_string($valor) && preg_match('/^(\d{4})-(\d{2})-(\d{2})( 00:00:00)?$/', $valor, $partes)) {
            return $partes[3].'/'.$partes[2].'/'.$partes[1];
        }

        return (string) $valor;
    }
}
