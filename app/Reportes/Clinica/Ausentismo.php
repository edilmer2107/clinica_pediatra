<?php

namespace App\Reportes\Clinica;

use App\Models\Appointment;
use App\Reportes\Reporte;

class Ausentismo extends Reporte
{
    public function clave(): string { return 'ausentismo'; }

    public function titulo(): string { return 'Ausentismo y cancelaciones'; }

    public function modulo(): string { return 'Gestión Clínica y Consultas'; }

    public function permiso(): string { return 'reportes.clinicos'; }

    public function descripcion(): string
    {
        return 'Porcentaje de citas canceladas o no asistidas (no-show), por médico y tipo de cita.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'doctor_id' => ['select', 'Profesional', $this->opcionesMedicos()],
            'modalidad' => ['select', 'Tipo de cita', ['presencial' => 'Presencial', 'telemedicina' => 'Telemedicina']],
        ];
    }

    public function columnas(): array
    {
        return ['Profesional', 'Tipo de cita', 'Citas', 'Atendidas', 'Canceladas', 'Reprogramadas', 'No asistió', '% cancelación', '% no-show'];
    }

    public function filas(array $f): array
    {
        return Appointment::with('doctor')
            ->whereBetween('fecha_hora', [$this->desde($f), $this->hasta($f)])
            ->when($f['doctor_id'] ?? null, fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($f['modalidad'] ?? null, fn ($q, $m) => $q->where('modalidad', $m))
            ->get()->groupBy(fn ($c) => $c->doctor_id.'|'.$c->modalidad)
            ->map(function ($g) {
                $total = $g->count();
                $canceladas = $g->where('estado', 'cancelada')->count();
                $noShow = $g->where('estado', 'no_asistio')->count();

                return [
                    $g->first()->doctor->name, ucfirst($g->first()->modalidad), $total,
                    $g->where('estado', 'atendida')->count(), $canceladas, $g->where('estado', 'reprogramada')->count(), $noShow,
                    round($canceladas / $total * 100, 1), round($noShow / $total * 100, 1),
                ];
            })->sortBy(fn ($r) => $r[0].$r[1])->values()->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);
        $total = max(1, $c->sum(2));

        return [
            'Citas' => $c->sum(2),
            'Canceladas' => $c->sum(4),
            '% cancelación global' => round($c->sum(4) / $total * 100, 1).' %',
            '% no-show global' => round($c->sum(6) / $total * 100, 1).' %',
        ];
    }
}
