<?php

namespace App\Services;

use App\Models\Anexo;
use App\Models\MedicionLinea;
use App\Models\Rubro;

class HojaCalculo
{
    public const FIJAS = ['descripcion', 'base1', 'base2', 'altura', 'numero', 'longitud', 'area', 'volumen', 'total'];

    /** @var list<string> */
    private array $claves;

    /** @var list<array<string, string>> */
    private array $raw;

    /** @var array<int, array<string, ?float>> */
    private array $val;

    private string $tipo;

    /** @var list<string> */
    private array $etiquetas;

    private int $filaFormula = 0;

    private string $src = '';

    private int $i = 0;

    /** @var array<string, true> */
    private array $pila = [];

    /** @var array<string, ?float> */
    private array $memo = [];

    private bool $ciclo = false;

    /**
     * @return list<array{clave: string, etiqueta: string}>
     */
    public static function columnasIniciales(string $tipo): array
    {
        return [
            ['clave' => 'descripcion', 'etiqueta' => 'Descripción'],
            ['clave' => 'base1', 'etiqueta' => 'Base 1'],
            ['clave' => 'base2', 'etiqueta' => 'Base 2'],
            ['clave' => 'altura', 'etiqueta' => 'Altura'],
            ['clave' => 'numero', 'etiqueta' => $tipo === UnidadMedicion::M3KM ? 'Km' : 'Número'],
            ['clave' => 'longitud', 'etiqueta' => 'Longitud'],
            ['clave' => 'area', 'etiqueta' => 'Área'],
            ['clave' => 'volumen', 'etiqueta' => 'Volumen'],
            ['clave' => 'total', 'etiqueta' => 'Total'],
        ];
    }

    /**
     * @param  array<int, mixed>|null  $guardadas
     * @return list<array{clave: string, etiqueta: string}>
     */
    public static function columnas(?array $guardadas, string $tipo): array
    {
        if ($guardadas === null || $guardadas === []) {
            return self::columnasIniciales($tipo);
        }

        $orden = [];
        $etiquetas = [];
        $formulas = [];
        foreach ($guardadas as $columna) {
            if (! is_array($columna)) {
                continue;
            }
            $clave = strtolower(trim((string) ($columna['clave'] ?? '')));
            $orden[] = $clave;
            $etiquetas[$clave] = (string) ($columna['etiqueta'] ?? '');
            $formulas[$clave] = (string) ($columna['formula'] ?? '');
        }

        return self::normalizar($orden, $etiquetas, $tipo, $formulas);
    }

    /**
     * @param  array<int, mixed>|null  $orden
     * @param  array<string, mixed>  $etiquetas
     * @param  array<string, mixed>  $formulas
     * @return list<array{clave: string, etiqueta: string, formula?: string}>
     */
    public static function normalizar(?array $orden, array $etiquetas, string $tipo, array $formulas = []): array
    {
        if ($orden === null || $orden === []) {
            return self::columnasIniciales($tipo);
        }

        $columnas = [];
        $vistas = [];
        foreach ($orden as $clave) {
            $clave = strtolower(trim((string) $clave));
            if (! preg_match('/^[a-z][a-z0-9_]{0,30}$/', $clave) || isset($vistas[$clave])) {
                continue;
            }
            $vistas[$clave] = true;
            $etiqueta = trim((string) ($etiquetas[$clave] ?? ''));
            if ($etiqueta === '') {
                $etiqueta = $clave === 'total' ? 'Total' : 'Columna';
            }
            $columna = ['clave' => $clave, 'etiqueta' => mb_substr($etiqueta, 0, 40)];
            $formula = self::formulaEscrita($formulas[$clave] ?? '');
            if ($formula !== '') {
                $columna['formula'] = $formula;
            }
            $columnas[] = $columna;
            if (count($columnas) >= 24) {
                break;
            }
        }

        if (! isset($vistas['total'])) {
            $columnas[] = ['clave' => 'total', 'etiqueta' => 'Total'];
        }

        return $columnas === [] ? self::columnasIniciales($tipo) : $columnas;
    }

    /**
     * @param  list<array<string, string>>  $crudas
     * @param  list<array{clave: string, etiqueta: string}>  $columnas
     * @return list<array{descripcion: string, numeros: array<string, ?float>, celdas: array<string, string>}>
     */
    public static function resolver(array $crudas, array $columnas, string $tipo): array
    {
        $claves = array_column($columnas, 'clave');
        $val = [];
        foreach ($crudas as $r => $fila) {
            foreach ($claves as $clave) {
                $val[$r][$clave] = null;
            }
        }

        $etiquetas = [];
        foreach ($columnas as $columna) {
            $etiquetas[] = (string) ($columna['etiqueta'] ?? '');
        }
        $motor = new self($crudas, $claves, $val, $tipo, $etiquetas);
        for ($pasada = 0; $pasada < 12; $pasada++) {
            $antes = $val;
            foreach ($crudas as $r => $fila) {
                foreach ($claves as $c => $clave) {
                    if ($clave === 'descripcion') {
                        continue;
                    }
                    $numero = $motor->valorDe($r, $c);
                    $val[$r][$clave] = $numero === null ? null : round($numero, 2);
                }
            }
            $motor->val = $val;
            if ($val === $antes) {
                break;
            }
        }

        $salida = [];
        foreach ($crudas as $r => $fila) {
            $celdas = [];
            foreach ($claves as $clave) {
                $texto = trim((string) ($fila[$clave] ?? ''));
                if ($texto !== '') {
                    $celdas[$clave] = mb_substr($texto, 0, 500);
                }
            }
            $numeros = [];
            foreach ($claves as $clave) {
                if ($clave === 'descripcion') {
                    continue;
                }
                $numeros[$clave] = $val[$r][$clave] ?? null;
            }
            $salida[] = [
                'descripcion' => trim((string) ($fila['descripcion'] ?? '')),
                'numeros' => $numeros,
                'celdas' => $celdas,
            ];
        }

        return $salida;
    }

    /**
     * @return array{columnas: list<array{clave: string, etiqueta: string}>, filas: list<array<string, string>>}
     */
    public static function presentar(Anexo $anexo, string $tipo): array
    {
        $columnas = self::columnas($anexo->columnas, $tipo);
        $crudas = [];
        foreach ($anexo->lineas as $linea) {
            $fila = [];
            foreach ($columnas as $columna) {
                $fila[$columna['clave']] = self::crudo($linea, $columna['clave']);
            }
            $crudas[] = $fila;
        }
        $resueltas = self::resolver($crudas, $columnas, $tipo);
        $filas = [];
        foreach ($resueltas as $resuelta) {
            $fila = [];
            foreach ($columnas as $columna) {
                $clave = $columna['clave'];
                if ($clave === 'descripcion') {
                    $fila[$clave] = $resuelta['descripcion'];
                    continue;
                }
                $numero = $resuelta['numeros'][$clave] ?? null;
                $escrito = (string) ($resuelta['celdas'][$clave] ?? '');
                if ($numero === null) {
                    $fila[$clave] = str_starts_with($escrito, '=') ? '' : $escrito;
                } else {
                    $fila[$clave] = number_format($numero, 2, '.', '');
                }
            }
            $filas[] = $fila;
        }

        return ['columnas' => $columnas, 'filas' => $filas];
    }

    public static function crudo(MedicionLinea $linea, string $clave): string
    {
        $celdas = $linea->celdas ?? [];
        if (array_key_exists($clave, $celdas) && $celdas[$clave] !== null && $celdas[$clave] !== '') {
            return (string) $celdas[$clave];
        }
        if ($clave === 'descripcion') {
            return (string) ($linea->descripcion ?? '');
        }
        if (! in_array($clave, self::FIJAS, true)) {
            return '';
        }
        $valor = $linea->getAttribute($clave);
        if ($valor === null || $valor === '') {
            return '';
        }

        return self::textoNumero($valor);
    }

    /**
     * @return array{raw: string, visible: string}
     */
    public static function editor(?MedicionLinea $linea, string $clave, string $tipo = UnidadMedicion::NUMERO): array
    {
        if (! $linea) {
            return ['raw' => '', 'visible' => ''];
        }
        $celdas = $linea->celdas ?? [];
        if ($clave === 'descripcion') {
            $texto = array_key_exists('descripcion', $celdas)
                ? (string) $celdas['descripcion']
                : (string) ($linea->descripcion ?? '');

            return ['raw' => $texto, 'visible' => $texto];
        }
        $raw = array_key_exists($clave, $celdas) ? (string) $celdas[$clave] : '';
        if ($raw !== '') {
            return ['raw' => $raw, 'visible' => $raw];
        }
        if (! in_array($clave, self::FIJAS, true)) {
            return ['raw' => '', 'visible' => ''];
        }
        $visible = self::textoNumero($linea->getAttribute($clave));
        if (in_array($clave, ['base1', 'base2', 'altura', 'numero'], true)) {
            return ['raw' => $visible, 'visible' => $visible];
        }
        $guardado = [
            'base1' => $linea->base1 !== null ? (float) $linea->base1 : null,
            'base2' => $linea->base2 !== null ? (float) $linea->base2 : null,
            'altura' => $linea->altura !== null ? (float) $linea->altura : null,
            'numero' => $linea->numero !== null ? (float) $linea->numero : null,
            'longitud' => $linea->longitud !== null ? (float) $linea->longitud : null,
            'area' => $linea->area !== null ? (float) $linea->area : null,
            'volumen' => $linea->volumen !== null ? (float) $linea->volumen : null,
        ];
        if ($visible !== '' && $visible !== self::textoNumero(self::automatico($clave, $guardado, $tipo))) {
            return ['raw' => $visible, 'visible' => $visible];
        }

        return ['raw' => '', 'visible' => $visible];
    }

    public static function textoNumero(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }
        $texto = number_format((float) $valor, 2, '.', '');

        return rtrim(rtrim($texto, '0'), '.');
    }

    public static function aNumero(string $texto): ?float
    {
        $texto = trim(str_replace(' ', '', $texto));
        if ($texto === '' || ! is_numeric($texto)) {
            return null;
        }

        return (float) $texto;
    }

    public static function letra(int $indice): string
    {
        $n = $indice + 1;
        $letra = '';
        while ($n > 0) {
            $n--;
            $letra = chr(65 + ($n % 26)).$letra;
            $n = intdiv($n, 26);
        }

        return $letra;
    }

    /**
     * @param  array<int, mixed>  $medicion
     * @return list<array{clave: string, etiqueta: string, formula: string}>
     */
    public static function columnasDesdeMedicion(array $medicion): array
    {
        $datos = [];
        foreach ($medicion as $dato) {
            if (! is_array($dato)) {
                continue;
            }
            $etiqueta = trim((string) ($dato['etiqueta'] ?? ''));
            if ($etiqueta === '') {
                continue;
            }
            $formula = trim((string) ($dato['formula'] ?? ''));
            if ($formula !== '' && ! str_starts_with($formula, '=')) {
                $formula = '='.$formula;
            }
            $datos[] = [
                'etiqueta' => mb_substr($etiqueta, 0, 40),
                'formula' => mb_substr($formula, 0, 200),
            ];
        }
        if ($datos === []) {
            return [];
        }

        $ultima = count($datos) - 1;
        $columnas = [[
            'clave' => 'descripcion',
            'etiqueta' => 'Descripción',
            'formula' => '',
        ]];
        foreach ($datos as $indice => $dato) {
            $etiqueta = $dato['etiqueta'];
            $formula = $dato['formula'];
            if ($formula === '' && str_starts_with($etiqueta, '=')) {
                $formula = $etiqueta;
                $etiqueta = 'Total';
            }
            $columnas[] = [
                'clave' => $indice === $ultima ? 'total' : 'e'.$indice,
                'etiqueta' => $etiqueta,
                'formula' => $formula,
            ];
        }

        return $columnas;
    }

    /**
     * @return list<array{clave: string, etiqueta: string, formula: string}>
     */
    public static function plantillaPara(Rubro $rubro): array
    {
        if (! is_array($rubro->medicion) || $rubro->medicion === []) {
            return [];
        }

        return self::columnasDesdeMedicion($rubro->medicion);
    }

    public static function hojaVacia(Anexo $anexo): bool
    {
        foreach ($anexo->lineas as $linea) {
            if (trim((string) $linea->descripcion) !== '' || ! empty($linea->celdas)) {
                return false;
            }
            foreach (self::FIJAS as $campo) {
                if ($campo === 'descripcion') {
                    continue;
                }
                if ((float) $linea->getAttribute($campo) != 0.0) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  list<array{clave: string, etiqueta: string, formula?: string}>  $columnas
     * @param  list<array{clave: string, etiqueta: string, formula?: string}>  $plantilla
     * @param  iterable<mixed>  $lineas
     * @return list<array{clave: string, etiqueta: string, formula?: string}>
     */
    public static function conFormulas(array $columnas, array $plantilla, iterable $lineas): array
    {
        $porNombre = [];
        foreach ($plantilla as $columna) {
            $formula = self::formulaEscrita($columna['formula'] ?? '');
            if ($formula !== '') {
                $porNombre[self::claveTexto($columna['etiqueta'] ?? '')] = $formula;
            }
        }

        foreach ($columnas as $indice => $columna) {
            if (($columna['clave'] ?? '') === 'descripcion' || self::formulaEscrita($columna['formula'] ?? '') !== '') {
                continue;
            }
            $formula = $porNombre[self::claveTexto($columna['etiqueta'] ?? '')] ?? '';
            if ($formula === '') {
                $formula = self::formulaMasUsada($lineas, (string) $columna['clave']);
            }
            if ($formula !== '') {
                $columnas[$indice]['formula'] = $formula;
            }
        }

        return $columnas;
    }

    public static function formulaEscrita(mixed $formula): string
    {
        $formula = trim((string) $formula);
        if ($formula === '') {
            return '';
        }
        if (! str_starts_with($formula, '=')) {
            $formula = '='.$formula;
        }

        return mb_substr($formula, 0, 200);
    }

    public static function formulaPatron(string $formula): string
    {
        return preg_replace('/([A-Za-z]+)(\d+)/', '${1}1', $formula) ?? $formula;
    }

    /**
     * @param  iterable<mixed>  $lineas
     */
    private static function formulaMasUsada(iterable $lineas, string $clave): string
    {
        $conteo = [];
        foreach ($lineas as $linea) {
            $celdas = $linea instanceof MedicionLinea ? ($linea->celdas ?? []) : (is_array($linea) ? ($linea['celdas'] ?? []) : []);
            $texto = trim((string) (is_array($celdas) ? ($celdas[$clave] ?? '') : ''));
            if (! str_starts_with($texto, '=')) {
                continue;
            }
            $texto = self::formulaEscrita($texto);
            $patron = self::formulaPatron($texto);
            $conteo[$patron] = ($conteo[$patron] ?? 0) + 1;
        }
        if ($conteo === []) {
            return '';
        }
        arsort($conteo);

        return (string) array_key_first($conteo);
    }

    public static function formulaEnFila(string $formula, int $fila): string
    {
        $formula = trim($formula);
        if ($formula === '' || $fila < 1) {
            return $formula;
        }

        return preg_replace_callback('/([A-Za-z]+)1(?!\d)/', function (array $coincidencia) use ($fila): string {
            return $coincidencia[1].$fila;
        }, $formula) ?? $formula;
    }

    public static function claveTexto(?string $texto): string
    {
        $texto = mb_strtolower(trim((string) $texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return $texto;
    }

    public static function indiceLetra(string $letras): int
    {
        $n = 0;
        foreach (str_split(strtoupper($letras)) as $caracter) {
            if ($caracter < 'A' || $caracter > 'Z') {
                return -1;
            }
            $n = $n * 26 + (ord($caracter) - 64);
        }

        return $n - 1;
    }

    /**
     * @param  list<array<string, string>>  $raw
     * @param  list<string>  $claves
     * @param  array<int, array<string, ?float>>  $val
     * @param  list<string>  $etiquetas
     */
    private function __construct(array $raw, array $claves, array $val, string $tipo, array $etiquetas = [])
    {
        $this->raw = $raw;
        $this->claves = $claves;
        $this->val = $val;
        $this->tipo = $tipo;
        $this->etiquetas = $etiquetas;
    }

    public function valorDe(int $fila, int $col): ?float
    {
        $this->pila = [];
        $this->memo = [];
        $this->ciclo = false;

        return $this->leer($fila, $col, false);
    }

    private function leer(int $fila, int $col, bool $enFormula): ?float
    {
        $marca = $fila.':'.$col;
        if (array_key_exists($marca, $this->memo)) {
            $listo = $this->memo[$marca];

            return $enFormula ? ($listo ?? 0.0) : $listo;
        }
        if ($this->ciclo || isset($this->pila[$marca])) {
            $this->ciclo = true;

            return null;
        }
        $clave = $this->claves[$col] ?? null;
        if ($clave === null || $clave === 'descripcion') {
            return $enFormula ? 0.0 : null;
        }

        $texto = trim((string) ($this->raw[$fila][$clave] ?? ''));
        if (str_starts_with($texto, '=')) {
            $this->pila[$marca] = true;
            $src = $this->src;
            $pos = $this->i;
            $filaFormula = $this->filaFormula;
            $this->filaFormula = $fila;
            $this->src = substr($texto, 1);
            $this->i = 0;
            $valor = $this->expresion();
            $this->espacios();
            if ($this->ciclo || $this->i < strlen($this->src)) {
                $valor = null;
            }
            $this->src = $src;
            $this->i = $pos;
            $this->filaFormula = $filaFormula;
            unset($this->pila[$marca]);
            $this->memo[$marca] = $valor;

            return $enFormula ? ($valor ?? 0.0) : $valor;
        }
        if ($texto !== '') {
            $valor = self::aNumero($texto);
            $this->memo[$marca] = $valor;

            return $enFormula ? ($valor ?? 0.0) : $valor;
        }

        $actual = $this->val[$fila][$clave] ?? null;
        if ($actual !== null) {
            return (float) $actual;
        }
        $auto = self::automatico($clave, $this->val[$fila] ?? [], $this->tipo);
        if ($auto !== null) {
            return $auto;
        }

        return $enFormula ? 0.0 : null;
    }

    /**
     * @param  array<string, ?float>  $fila
     */
    private static function automatico(string $clave, array $fila, string $tipo): ?float
    {
        $dims = UnidadMedicion::desdeDimensiones([
            'base1' => $fila['base1'] ?? null,
            'base2' => $fila['base2'] ?? null,
            'altura' => $fila['altura'] ?? null,
            'numero' => $fila['numero'] ?? null,
        ], $tipo);

        if (in_array($clave, ['longitud', 'area', 'volumen'], true)) {
            return $dims[$clave];
        }
        if ($clave === 'total') {
            return UnidadMedicion::total(
                $tipo,
                isset($fila['longitud']) ? (float) $fila['longitud'] : $dims['longitud'],
                isset($fila['area']) ? (float) $fila['area'] : $dims['area'],
                isset($fila['volumen']) ? (float) $fila['volumen'] : $dims['volumen'],
                $fila['numero'] ?? null,
            );
        }

        return null;
    }

    private function expresion(): ?float
    {
        if ($this->ciclo) {
            return null;
        }
        $izq = $this->termino();
        if ($izq === null) {
            return null;
        }
        while (true) {
            $this->espacios();
            $op = $this->src[$this->i] ?? '';
            if ($op !== '+' && $op !== '-') {
                break;
            }
            $this->i++;
            $der = $this->termino();
            if ($der === null) {
                return null;
            }
            $izq = $op === '+' ? $izq + $der : $izq - $der;
        }

        return $izq;
    }

    private function termino(): ?float
    {
        $izq = $this->factor();
        if ($izq === null) {
            return null;
        }
        while (true) {
            $this->espacios();
            $op = $this->src[$this->i] ?? '';
            if ($op !== '*' && $op !== '/') {
                break;
            }
            $this->i++;
            $der = $this->factor();
            if ($der === null || ($op === '/' && $der == 0.0)) {
                return null;
            }
            $izq = $op === '*' ? $izq * $der : $izq / $der;
        }

        return $izq;
    }

    private function factor(): ?float
    {
        $this->espacios();
        $op = $this->src[$this->i] ?? '';
        if ($op === '+' || $op === '-') {
            $this->i++;
            $valor = $this->factor();

            return $valor === null ? null : ($op === '-' ? -$valor : $valor);
        }

        return $this->primario();
    }

    private function primario(): ?float
    {
        $this->espacios();
        $caracter = $this->src[$this->i] ?? '';
        if ($caracter === '(') {
            $this->i++;
            $valor = $this->expresion();
            $this->espacios();
            if (($this->src[$this->i] ?? '') !== ')') {
                return null;
            }
            $this->i++;

            return $valor;
        }
        if (preg_match('/\G(\d+(?:\.\d+)?)/A', $this->src, $numero, 0, $this->i)) {
            $this->i += strlen($numero[1]);

            return (float) $numero[1];
        }
        $columna = $this->consumirColumna();
        if ($columna !== null) {
            $referida = $this->leer($this->filaFormula, $columna, true);
            if ($this->ciclo) {
                return null;
            }

            return $referida ?? 0.0;
        }
        if (! preg_match('/\G([A-Za-z]+)/A', $this->src, $nombre, 0, $this->i)) {
            return null;
        }
        $this->i += strlen($nombre[1]);
        if (preg_match('/\G(\d+)/A', $this->src, $fila, 0, $this->i) && (($this->src[$this->i + strlen($fila[1])] ?? '') !== '(')) {
            $this->i += strlen($fila[1]);
            $col = self::indiceLetra($nombre[1]);
            $r = ((int) $fila[1]) - 1;
            if ($col < 0 || $col >= count($this->claves) || $r < 0 || $r >= count($this->raw)) {
                return 0.0;
            }

            $referida = $this->leer($r, $col, true);
            if ($this->ciclo) {
                return null;
            }

            return $referida ?? 0.0;
        }
        $this->espacios();
        if (($this->src[$this->i] ?? '') !== '(') {
            return null;
        }
        $this->i++;
        $args = $this->argumentos();
        $this->espacios();
        if ($args === null || ($this->src[$this->i] ?? '') !== ')') {
            return null;
        }
        $this->i++;

        return $this->funcion(strtoupper($nombre[1]), $args);
    }

    /**
     * @return list<float>|null
     */
    private function argumentos(): ?array
    {
        $numeros = [];
        $this->espacios();
        if (($this->src[$this->i] ?? '') === ')') {
            return [];
        }
        while (true) {
            $bloque = $this->rangoOExpresion();
            if ($bloque === null) {
                return null;
            }
            foreach ($bloque as $numero) {
                $numeros[] = $numero;
            }
            $this->espacios();
            $sep = $this->src[$this->i] ?? '';
            if ($sep === ',' || $sep === ';') {
                $this->i++;
                continue;
            }
            break;
        }

        return $numeros;
    }

    /**
     * @return list<float>|null
     */
    private function rangoOExpresion(): ?array
    {
        $this->espacios();
        if (preg_match('/\G([A-Za-z]+)(\d+)\s*:/A', $this->src, $inicio, 0, $this->i)) {
            $c1 = self::indiceLetra($inicio[1]);
            $r1 = ((int) $inicio[2]) - 1;
            $this->i += strlen($inicio[0]);
            $this->espacios();
            if (! preg_match('/\G([A-Za-z]+)(\d+)/A', $this->src, $fin, 0, $this->i)) {
                return null;
            }
            $this->i += strlen($fin[0]);
            $c2 = self::indiceLetra($fin[1]);
            $r2 = ((int) $fin[2]) - 1;
            $numeros = [];
            for ($r = min($r1, $r2); $r <= max($r1, $r2); $r++) {
                for ($c = min($c1, $c2); $c <= max($c1, $c2); $c++) {
                    if ($r < 0 || $c < 0 || $r >= count($this->raw) || $c >= count($this->claves)) {
                        $numeros[] = 0.0;
                        continue;
                    }
                    $referida = $this->leer($r, $c, true);
                    if ($this->ciclo) {
                        return null;
                    }
                    $numeros[] = $referida ?? 0.0;
                }
            }

            return $numeros;
        }
        $valor = $this->expresion();

        return $valor === null ? null : [$valor];
    }

    /**
     * @param  list<float>  $args
     */
    private function funcion(string $nombre, array $args): ?float
    {
        return match ($nombre) {
            'SUMA', 'SUM' => round(array_sum($args), 2),
            'PROMEDIO', 'PROM', 'AVERAGE' => $args === [] ? null : round(array_sum($args) / count($args), 2),
            'MIN' => $args === [] ? null : min($args),
            'MAX' => $args === [] ? null : max($args),
            'ABS' => isset($args[0]) ? abs($args[0]) : null,
            'REDONDEAR', 'ROUND' => isset($args[0]) ? round($args[0], (int) ($args[1] ?? 0)) : null,
            default => null,
        };
    }

    private function consumirColumna(): ?int
    {
        $resto = substr($this->src, $this->i);
        $mejor = null;
        $mejorLargo = 0;
        foreach ($this->etiquetas as $columna => $etiqueta) {
            $etiqueta = trim($etiqueta);
            if ($etiqueta === '' || str_starts_with($etiqueta, '=')) {
                continue;
            }
            $patron = self::patronNombre($etiqueta);
            if ($patron === '' || ! preg_match('/\A'.$patron.'/iu', $resto, $coincidencia)) {
                continue;
            }
            $largo = strlen($coincidencia[0]);
            $siguiente = $resto[$largo] ?? '';
            if ($siguiente === '(' || ($siguiente !== '' && preg_match('/[A-Za-z0-9_]/', $siguiente))) {
                continue;
            }
            if ($largo > $mejorLargo) {
                $mejorLargo = $largo;
                $mejor = $columna;
            }
        }
        if ($mejor === null) {
            return null;
        }
        $this->i += $mejorLargo;

        return $mejor;
    }

    private static function patronNombre(string $etiqueta): string
    {
        $partes = [];
        foreach (preg_split('//u', $etiqueta, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $caracter) {
            if (preg_match('/\s/u', $caracter)) {
                $partes[] = '\s+';

                continue;
            }
            $base = strtr(mb_strtolower($caracter), ['á' => 'a', 'à' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'ñ' => 'n']);
            $partes[] = match ($base) {
                'a' => '[aáàä]',
                'e' => '[eéèë]',
                'i' => '[iíìï]',
                'o' => '[oóòö]',
                'u' => '[uúùü]',
                'n' => '[nñ]',
                default => preg_quote($base, '/'),
            };
        }

        return implode('', $partes);
    }

    private function espacios(): void
    {
        while (($this->src[$this->i] ?? '') === ' ') {
            $this->i++;
        }
    }
}
