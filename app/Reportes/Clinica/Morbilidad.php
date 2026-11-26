<?php

namespace App\Reportes\Clinica;

use App\Models\ConsultationDiagnosis;
use App\Reportes\Reporte;

class Morbilidad extends Reporte
{
    public function clave(): string { return 'morbilidad'; }

    public function titulo(): string { return 'Diagnósticos frecuentes (morbilidad CIE-10)'; }

    public function modulo(): string { return 'Gestión Clínica y Consultas'; }

    public function permiso(): string { return 'reportes.clinicos'; }

    public function descripcion(): string
    {
        return 'Diagnósticos CIE-10 más comunes, filtrable por grupo de edad, sexo y profesional.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'tipo_paciente' => ['select', 'Grupo de edad', self::gruposEdad()],
            'sexo' => ['select', 'Sexo', ['M' => 'Masculino', 'F' => 'Femenino']],
            'doctor_id' => ['select', 'Profesional', $this->opcionesMedicos()],
        ];
    }

    public function columnas(): array
    {
        return ['N°', 'Código CIE-10', 'Diagnóstico', 'Casos', 'Masculino', 'Femenino', '% del total'];
    }

    public function filas(array $f): array
    {
        $diagnosticos = ConsultationDiagnosis::with(['cie10', 'consultation.patient'])
            ->whereHas('consultation', fn ($q) => $q->whereBetween('fecha', [$this->desde($f), $this->hasta($f)])
                ->when($f['doctor_id'] ?? null, fn ($w, $id) => $w->where('doctor_id', $id)))
            ->get()
            ->filter(function ($d) use ($f) {
                $p = $d->consultation->patient;
                if (! empty($f['sexo']) && $p->sexo !== $f['sexo']) {
                    return false;
                }

                return empty($f['tipo_paciente']) || self::grupoDeEdad($p->fecha_nacimiento, $d->consultation->fecha) === $f['tipo_paciente'];
            });

        $total = max(1, $diagnosticos->count());
        $n = 0;

        return $diagnosticos->groupBy('cie10_code_id')
            ->sortByDesc(fn ($g) => $g->count())
            ->map(function ($g) use ($total, &$n) {
                $n++;

                return [
                    $n, $g->first()->cie10->codigo, $g->first()->cie10->descripcion, $g->count(),
                    $g->filter(fn ($d) => $d->consultation->patient->sexo === 'M')->count(),
                    $g->filter(fn ($d) => $d->consultation->patient->sexo === 'F')->count(),
                    round($g->count() / $total * 100, 1),
                ];
            })->values()->all();
    }

    public function resumen(array $f, array $filas): array
    {
        return ['Diagnósticos registrados' => collect($filas)->sum(3), 'Códigos distintos' => count($filas), 'Más frecuente' => $filas[0][1] ?? '—'];
    }

    public function grafico(array $f, array $filas): ?array
    {
        $top = array_slice($filas, 0, 10);

        return ['tipo' => 'bar', 'labels' => array_column($top, 1), 'series' => ['Casos' => array_column($top, 3)]];
    }
}
