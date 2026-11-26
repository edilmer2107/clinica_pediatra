<?php

namespace App\Services\Clinica;

use App\Models\DrugInteraction;
use App\Models\Medication;
use InvalidArgumentException;

/**
 * Cálculo de dosis pediátrica por peso (mg/kg/día) o superficie corporal (mg/m²/día)
 * con validación contra los rangos terapéuticos del medicamento.
 */
class DosisPediatricaService
{
    /** Superficie corporal (m²) por la fórmula de Mosteller: √(talla cm × peso kg / 3600). */
    public static function superficieCorporal(float $pesoKg, float $tallaCm): float
    {
        if ($pesoKg <= 0 || $tallaCm <= 0) {
            throw new InvalidArgumentException('Peso y talla deben ser mayores a cero.');
        }

        return round(sqrt($tallaCm * $pesoKg / 3600), 3);
    }

    /**
     * @return array{dosis_diaria_mg: float, dosis_por_toma_mg: float, volumen_por_toma_ml: ?float, tomas_dia: int, cantidad: int, alertas: array<int, string>, alerta_dosis: bool}
     */
    public function calcular(Medication $med, string $metodo, float $dosisIndicada, float $pesoKg, ?float $tallaCm, int $frecuenciaHoras, int $duracionDias): array
    {
        if (! in_array($metodo, ['peso', 'sc'], true)) {
            throw new InvalidArgumentException('Método de cálculo no válido.');
        }
        if ($pesoKg <= 0 || $dosisIndicada <= 0) {
            throw new InvalidArgumentException('El peso y la dosis indicada deben ser mayores a cero.');
        }
        if ($frecuenciaHoras <= 0 || 24 % $frecuenciaHoras !== 0) {
            throw new InvalidArgumentException('La frecuencia debe dividir exactamente las 24 horas (4, 6, 8, 12 o 24 h).');
        }
        if ($duracionDias <= 0) {
            throw new InvalidArgumentException('La duración debe ser de al menos 1 día.');
        }

        $alertas = [];

        if ($metodo === 'sc') {
            if (! $tallaCm) {
                throw new InvalidArgumentException('Para el cálculo por superficie corporal se requiere la talla.');
            }
            $sc = self::superficieCorporal($pesoKg, $tallaCm);
            $diaria = $dosisIndicada * $sc;
            [$min, $max, $unidad] = [$med->dosis_min_mg_m2_dia, $med->dosis_max_mg_m2_dia, 'mg/m²/día'];
        } else {
            $diaria = $dosisIndicada * $pesoKg;
            [$min, $max, $unidad] = [$med->dosis_min_mg_kg_dia, $med->dosis_max_mg_kg_dia, 'mg/kg/día'];
        }

        if ($max !== null && $dosisIndicada > $max) {
            $alertas[] = "Dosis de {$dosisIndicada} {$unidad} EXCEDE el máximo pediátrico de {$max} {$unidad}.";
        }
        if ($min !== null && $dosisIndicada < $min) {
            $alertas[] = "Dosis de {$dosisIndicada} {$unidad} es INFERIOR al mínimo terapéutico de {$min} {$unidad}.";
        }
        if ($med->dosis_max_diaria_mg !== null && $diaria > $med->dosis_max_diaria_mg) {
            $alertas[] = 'Dosis diaria de '.round($diaria, 1)." mg supera la dosis máxima absoluta de {$med->dosis_max_diaria_mg} mg/día.";
        }

        $tomas = intdiv(24, $frecuenciaHoras);
        $porToma = $diaria / $tomas;
        $volumen = $med->mg_por_ml ? round($porToma / $med->mg_por_ml, 2) : null;
        $totalMg = $diaria * $duracionDias;
        $cantidad = $med->mg_por_unidad ? max(1, (int) ceil(round($totalMg / $med->mg_por_unidad, 4))) : max(1, $tomas * $duracionDias);

        return [
            'dosis_diaria_mg' => round($diaria, 3),
            'dosis_por_toma_mg' => round($porToma, 3),
            'volumen_por_toma_ml' => $volumen,
            'tomas_dia' => $tomas,
            'cantidad' => $cantidad,
            'alertas' => $alertas,
            'alerta_dosis' => $alertas !== [],
        ];
    }

    /**
     * Interacciones entre los principios activos indicados.
     *
     * @param  array<int, string>  $principios
     * @return array<int, array{a: string, b: string, severidad: string, descripcion: string}>
     */
    public function interacciones(array $principios): array
    {
        $principios = array_values(array_unique(array_map(fn ($p) => mb_strtolower(trim($p)), $principios)));
        if (count($principios) < 2) {
            return [];
        }

        return DrugInteraction::query()
            ->whereIn('principio_a', $principios)
            ->whereIn('principio_b', $principios)
            ->get()
            ->filter(fn ($i) => $i->principio_a !== $i->principio_b)
            ->map(fn ($i) => ['a' => $i->principio_a, 'b' => $i->principio_b, 'severidad' => $i->severidad, 'descripcion' => $i->descripcion])
            ->values()->all();
    }
}
