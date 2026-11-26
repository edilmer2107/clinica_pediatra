<?php

namespace App\Reportes\Pacientes;

use App\Models\Immunization;
use App\Models\VaccinationGoal;
use App\Models\Vaccine;
use App\Reportes\Reporte;

class CoberturaVacunacion extends Reporte
{
    public function clave(): string { return 'cobertura-vacunacion'; }

    public function titulo(): string { return 'Cobertura de vacunación (esquema regular)'; }

    public function modulo(): string { return 'Pacientes Pediátricos'; }

    public function permiso(): string { return 'reportes.clinicos'; }

    public function descripcion(): string
    {
        return 'Meta, dosis aplicadas y brecha por grupo de edad y vacuna, para consolidación ante el MINSA.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'vaccine_id' => ['select', 'Vacuna', Vaccine::orderBy('nombre')->pluck('nombre', 'id')->all()],
        ];
    }

    protected function desdePorDefecto(): string
    {
        return today()->startOfYear()->toDateString();
    }

    public function columnas(): array
    {
        return ['Grupo de edad', 'Vacuna', 'Dosis del esquema', 'Meta anual', 'Dosis aplicadas', 'Brecha', 'Cobertura (%)'];
    }

    public function filas(array $f): array
    {
        $anio = (int) $this->desde($f)->year;
        $aplicadas = Immunization::query()
            ->join('vaccine_schedule_doses as d', 'd.id', '=', 'immunizations.vaccine_schedule_dose_id')
            ->whereBetween('fecha_aplicacion', [$this->desde($f), $this->hasta($f)])
            ->selectRaw('immunizations.vaccine_id, d.grupo_edad, COUNT(*) as total')
            ->groupBy('immunizations.vaccine_id', 'd.grupo_edad')
            ->get()->keyBy(fn ($r) => $r->vaccine_id.'|'.$r->grupo_edad);

        return VaccinationGoal::with(['vaccine.scheduleDoses'])
            ->where('anio', $anio)
            ->when($f['vaccine_id'] ?? null, fn ($q, $id) => $q->where('vaccine_id', $id))
            ->get()
            ->sortBy(fn ($m) => [$this->ordenGrupo($m->grupo_edad), $m->vaccine->nombre])
            ->map(function ($meta) use ($aplicadas) {
                $n = (int) ($aplicadas[$meta->vaccine_id.'|'.$meta->grupo_edad]->total ?? 0);
                $dosis = $meta->vaccine->scheduleDoses->where('grupo_edad', $meta->grupo_edad)->pluck('nombre_dosis')->implode(', ');

                return [
                    $meta->grupo_edad,
                    $meta->vaccine->nombre,
                    $dosis,
                    $meta->meta,
                    $n,
                    max(0, $meta->meta - $n),
                    $meta->meta > 0 ? round($n / $meta->meta * 100, 1) : 0,
                ];
            })->values()->all();
    }

    private function ordenGrupo(string $g): int
    {
        return ['Recién nacido' => 0, 'Menor de 1 año' => 1, '1 año' => 2, '4 años' => 3][$g] ?? 9;
    }

    public function resumen(array $f, array $filas): array
    {
        $meta = collect($filas)->sum(fn ($r) => $r[3]);
        $aplicadas = collect($filas)->sum(fn ($r) => $r[4]);

        return [
            'Meta total' => $meta,
            'Dosis aplicadas' => $aplicadas,
            'Brecha total' => collect($filas)->sum(fn ($r) => $r[5]),
            'Cobertura global' => ($meta ? round($aplicadas / $meta * 100, 1) : 0).' %',
        ];
    }

    public function grafico(array $f, array $filas): ?array
    {
        return [
            'tipo' => 'bar',
            'labels' => array_map(fn ($r) => $r[1].' ('.$r[0].')', $filas),
            'series' => ['Meta' => array_column($filas, 3), 'Aplicadas' => array_column($filas, 4)],
        ];
    }
}
