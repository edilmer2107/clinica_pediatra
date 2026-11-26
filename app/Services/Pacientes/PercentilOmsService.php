<?php

namespace App\Services\Pacientes;

use App\Models\AnthropometricMeasurement;
use App\Models\Patient;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Cálculo de puntuación Z y percentil según los Patrones de Crecimiento Infantil
 * de la OMS (2006) mediante el método LMS:
 *
 *   Z = ((X / M)^L − 1) / (L · S)        si L ≠ 0
 *   Z = ln(X / M) / S                    si L = 0
 *
 * Los parámetros L, M y S se interpolan linealmente a la edad exacta en días.
 * Para indicadores de peso e IMC se aplica la corrección de la OMS para |Z| > 3.
 * Rango de aplicación: 0 a 60 meses (1856 días).
 */
class PercentilOmsService
{
    public const DIAS_POR_MES = 30.4375;

    public const EDAD_MAXIMA_DIAS = 1856;

    /** Líneas de percentil de referencia (P3, P15, P50, P85, P97) expresadas en Z. */
    public const LINEAS_Z = [-1.881, -1.036, 0.0, 1.036, 1.881];

    public const INDICADORES = [
        'wfa' => 'Peso para la edad',
        'lhfa' => 'Longitud/talla para la edad',
        'hcfa' => 'Perímetro cefálico para la edad',
        'bmifa' => 'IMC para la edad',
    ];

    private static ?array $tablas = null;

    private function tablas(): array
    {
        if (self::$tablas === null) {
            $json = json_decode(file_get_contents(database_path('data/who_lms.json')), true, 512, JSON_THROW_ON_ERROR);
            self::$tablas = $json['data'];
        }

        return self::$tablas;
    }

    /**
     * Parámetros LMS interpolados para la edad exacta en días.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    public function lms(string $indicador, string $sexo, int $edadDias): array
    {
        if (! isset(self::INDICADORES[$indicador])) {
            throw new InvalidArgumentException("Indicador no soportado: {$indicador}");
        }
        if (! in_array($sexo, ['M', 'F'], true)) {
            throw new InvalidArgumentException('El sexo debe ser M o F.');
        }
        if ($edadDias < 0 || $edadDias > self::EDAD_MAXIMA_DIAS) {
            throw new InvalidArgumentException('Edad fuera del rango de los patrones OMS (0 a 60 meses).');
        }

        $tabla = $this->tablas()[$indicador][$sexo];
        $meses = $edadDias / self::DIAS_POR_MES;
        $m0 = (int) floor($meses);
        $m1 = min($m0 + 1, 60);
        $fraccion = $meses - $m0;

        [$l0, $mm0, $s0] = $tabla[(string) min($m0, 60)];
        [$l1, $mm1, $s1] = $tabla[(string) $m1];

        return [
            $l0 + ($l1 - $l0) * $fraccion,
            $mm0 + ($mm1 - $mm0) * $fraccion,
            $s0 + ($s1 - $s0) * $fraccion,
        ];
    }

    public function zScore(string $indicador, string $sexo, int $edadDias, float $valor): float
    {
        if ($valor <= 0) {
            throw new InvalidArgumentException('La medida debe ser mayor a cero.');
        }

        [$l, $m, $s] = $this->lms($indicador, $sexo, $edadDias);
        $z = abs($l) < 1e-9 ? log($valor / $m) / $s : (pow($valor / $m, $l) - 1) / ($l * $s);

        // Corrección OMS para valores extremos en indicadores de peso
        if (in_array($indicador, ['wfa', 'bmifa'], true) && abs($z) > 3) {
            $sd = fn (float $k) => $m * pow(1 + $l * $s * $k, 1 / $l);
            if ($z > 3) {
                $z = 3 + ($valor - $sd(3)) / ($sd(3) - $sd(2));
            } else {
                $z = -3 + ($valor - $sd(-3)) / ($sd(-2) - $sd(-3));
            }
        }

        return round($z, 2);
    }

    /** Percentil (0–100) a partir de Z usando la función de distribución normal. */
    public function percentil(float $z): float
    {
        return round($this->normalCdf($z) * 100, 1);
    }

    /** Valor de la medida para un Z dado (para dibujar curvas de referencia). */
    public function valorParaZ(string $indicador, string $sexo, int $edadDias, float $z): float
    {
        [$l, $m, $s] = $this->lms($indicador, $sexo, $edadDias);

        return abs($l) < 1e-9 ? $m * exp($s * $z) : $m * pow(1 + $l * $s * $z, 1 / $l);
    }

    public static function imc(float $pesoKg, float $tallaCm): float
    {
        $metros = $tallaCm / 100;

        return round($pesoKg / ($metros * $metros), 2);
    }

    /**
     * Evalúa una medición completa: Z, percentiles y alertas.
     *
     * @return array<string, mixed>
     */
    /**
     * @param  iterable<AnthropometricMeasurement>|AnthropometricMeasurement|null  $previas  controles anteriores (se usan los de los últimos 6 meses)
     */
    public function evaluar(Patient $paciente, Carbon $fecha, float $peso, float $talla, ?float $pc, iterable|AnthropometricMeasurement|null $previas = null): array
    {
        $edadDias = (int) $paciente->fecha_nacimiento->diffInDays($fecha);
        $imc = self::imc($peso, $talla);
        $resultado = ['edad_dias' => $edadDias, 'imc' => $imc, 'alertas' => []];

        if ($edadDias > self::EDAD_MAXIMA_DIAS) {
            $resultado['alertas'][] = ['tipo' => 'info', 'mensaje' => 'Paciente mayor de 5 años: fuera del rango de los patrones OMS 0-5 años; no se calculan percentiles.'];

            return $resultado;
        }

        $valores = ['peso_edad' => ['wfa', $peso], 'talla_edad' => ['lhfa', $talla], 'imc_edad' => ['bmifa', $imc]];
        if ($pc) {
            $valores['pc_edad'] = ['hcfa', $pc];
        }

        foreach ($valores as $campo => [$indicador, $valor]) {
            $z = $this->zScore($indicador, $paciente->sexo, $edadDias, $valor);
            $resultado["z_{$campo}"] = $z;
            $resultado["p_{$campo}"] = $this->percentil($z);
        }

        $resultado['alertas'] = array_merge(
            $this->alertasPorRango($resultado),
            $this->alertasPorCruce($resultado, $previas, $fecha),
        );

        return $resultado;
    }

    /** Alertas por valores fuera de rango (Z < −2 o Z > +2). */
    public function alertasPorRango(array $r): array
    {
        $alertas = [];
        $reglas = [
            'z_peso_edad' => [-3 => 'Bajo peso severo', -2 => 'Bajo peso', 2 => 'Peso elevado para la edad'],
            'z_talla_edad' => [-3 => 'Talla baja severa', -2 => 'Talla baja', 3 => 'Talla muy alta'],
            'z_imc_edad' => [-3 => 'Emaciación severa', -2 => 'Emaciación', 2 => 'Sobrepeso', 3 => 'Obesidad'],
            'z_pc_edad' => [-2 => 'Microcefalia (PC < −2 DE)', 2 => 'Macrocefalia (PC > +2 DE)'],
        ];

        foreach ($reglas as $campo => $umbrales) {
            if (! isset($r[$campo])) {
                continue;
            }
            $z = $r[$campo];
            $mensaje = null;
            foreach ($umbrales as $umbral => $texto) {
                if (($umbral < 0 && $z < $umbral) || ($umbral > 0 && $z > $umbral)) {
                    $mensaje = $texto; // el umbral más extremo que se cumpla prevalece
                    if ($umbral < 0) {
                        break;
                    }
                }
            }
            if ($mensaje) {
                $alertas[] = ['tipo' => 'rango', 'indicador' => $campo, 'z' => $z, 'mensaje' => "{$mensaje} (Z = {$z})"];
            }
        }

        return $alertas;
    }

    /** Ventana de comparación para detectar caídas progresivas. */
    public const DIAS_VENTANA_CRUCE = 183;

    /**
     * Alerta de cruce de percentiles: cuando, respecto a cualquier control de los
     * últimos 6 meses, el Z cruza dos o más líneas de percentil principales
     * (P3, P15, P50, P85, P97). Así se detectan también las caídas progresivas.
     */
    public function alertasPorCruce(array $r, iterable|AnthropometricMeasurement|null $previas, ?Carbon $fecha = null): array
    {
        $previas = match (true) {
            $previas === null => [],
            $previas instanceof AnthropometricMeasurement => [$previas],
            default => $previas,
        };
        $fecha ??= today();

        $alertas = [];
        $campos = ['z_peso_edad' => 'peso', 'z_talla_edad' => 'talla', 'z_pc_edad' => 'perímetro cefálico'];

        foreach ($campos as $campo => $nombre) {
            if (! isset($r[$campo])) {
                continue;
            }
            $peor = null;
            foreach ($previas as $previa) {
                if ($previa->{$campo} === null || ($previa->fecha && $previa->fecha->diffInDays($fecha) > self::DIAS_VENTANA_CRUCE)) {
                    continue;
                }
                $lineas = self::lineasCruzadas((float) $previa->{$campo}, (float) $r[$campo]);
                if ($lineas >= 2 && ($peor === null || $lineas > $peor[0])) {
                    $peor = [$lineas, (float) $previa->{$campo}];
                }
            }
            if ($peor) {
                [$lineas, $zPrevio] = $peor;
                $direccion = $r[$campo] < $zPrevio ? 'Caída' : 'Ascenso';
                $alertas[] = [
                    'tipo' => 'cruce', 'indicador' => $campo, 'z' => $r[$campo],
                    'mensaje' => "{$direccion} de {$lineas} líneas de percentil en {$nombre} (Z {$zPrevio} → {$r[$campo]})",
                ];
            }
        }

        return $alertas;
    }

    public static function lineasCruzadas(float $zAnterior, float $zActual): int
    {
        $min = min($zAnterior, $zActual);
        $max = max($zAnterior, $zActual);

        return count(array_filter(self::LINEAS_Z, fn ($linea) => $linea > $min && $linea <= $max));
    }

    /** Aproximación de la función de distribución normal estándar (Abramowitz-Stegun 26.2.17). */
    private function normalCdf(float $z): float
    {
        $t = 1 / (1 + 0.2316419 * abs($z));
        $d = 0.3989422804014327 * exp(-$z * $z / 2);
        $p = $d * $t * (0.319381530 + $t * (-0.356563782 + $t * (1.781477937 + $t * (-1.821255978 + $t * 1.330274429))));

        return $z >= 0 ? 1 - $p : $p;
    }
}
