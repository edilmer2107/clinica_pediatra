<?php

namespace App\Reportes;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Contrato común de reportes: se visualizan en pantalla, se imprimen y se
 * exportan a PDF y Excel con los mismos filtros (RF transversal).
 */
abstract class Reporte
{
    /** Identificador usado en la URL. */
    abstract public function clave(): string;

    abstract public function titulo(): string;

    abstract public function modulo(): string;

    abstract public function permiso(): string;

    public function descripcion(): string
    {
        return '';
    }

    /**
     * Filtros disponibles: nombre => [tipo, etiqueta, opciones?].
     * Tipos: date, select, number.
     *
     * @return array<string, array<int, mixed>>
     */
    public function filtros(): array
    {
        return [
            'desde' => ['date', 'Desde'],
            'hasta' => ['date', 'Hasta'],
        ];
    }

    /** @return array<int, string> */
    abstract public function columnas(): array;

    /** @return array<int, array<int, mixed>> */
    abstract public function filas(array $f): array;

    /** Totales o indicadores de resumen: etiqueta => valor. */
    public function resumen(array $f, array $filas): array
    {
        return ['Registros' => count($filas)];
    }

    /** Datos opcionales para un gráfico: ['tipo' => 'bar', 'labels' => [], 'series' => [etiqueta => []]]. */
    public function grafico(array $f, array $filas): ?array
    {
        return null;
    }

    public function valoresFiltros(Request $request): array
    {
        $valores = [];
        foreach ($this->filtros() as $nombre => $def) {
            $valor = $request->query($nombre);
            $valores[$nombre] = is_string($valor) ? trim($valor) : $valor;
        }
        $valores['desde'] = $valores['desde'] ?? null ?: $this->desdePorDefecto();
        $valores['hasta'] = $valores['hasta'] ?? null ?: today()->toDateString();

        return $valores;
    }

    protected function desdePorDefecto(): string
    {
        return today()->subMonths(3)->toDateString();
    }

    protected function desde(array $f): Carbon
    {
        return Carbon::parse($f['desde'])->startOfDay();
    }

    protected function hasta(array $f): Carbon
    {
        return Carbon::parse($f['hasta'])->endOfDay();
    }

    protected function opcionesMedicos(): array
    {
        return User::role('medico')->orderBy('name')->pluck('name', 'id')->all();
    }

    /** Grupos etarios pediátricos usados como "tipo de paciente". */
    public static function gruposEdad(): array
    {
        return [
            'neonato' => 'Neonato (0-28 d)',
            'lactante' => 'Lactante (29 d - 1 a)',
            'preescolar' => 'Preescolar (1-5 a)',
            'escolar' => 'Escolar (6-11 a)',
            'adolescente' => 'Adolescente (12-17 a)',
        ];
    }

    /** Rango de fechas de nacimiento [min, max] para un grupo etario a la fecha de hoy. */
    public static function rangoNacimiento(string $grupo): array
    {
        $hoy = today();

        return match ($grupo) {
            'neonato' => [$hoy->copy()->subDays(28), $hoy->copy()],
            'lactante' => [$hoy->copy()->subYear()->addDay(), $hoy->copy()->subDays(29)],
            'preescolar' => [$hoy->copy()->subYears(6)->addDay(), $hoy->copy()->subYear()],
            'escolar' => [$hoy->copy()->subYears(12)->addDay(), $hoy->copy()->subYears(6)],
            'adolescente' => [$hoy->copy()->subYears(18)->addDay(), $hoy->copy()->subYears(12)],
            default => [$hoy->copy()->subYears(120), $hoy->copy()],
        };
    }

    public static function grupoDeEdad(Carbon $nacimiento, ?Carbon $fecha = null): string
    {
        $fecha ??= today();
        $dias = $nacimiento->diffInDays($fecha);
        $anios = $nacimiento->diffInYears($fecha);

        return match (true) {
            $dias <= 28 => 'neonato',
            $anios < 1 => 'lactante',
            $anios < 6 => 'preescolar',
            $anios < 12 => 'escolar',
            default => 'adolescente',
        };
    }
}
