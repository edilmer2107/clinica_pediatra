<?php

namespace App\Reportes\Clinica;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Reportes\Reporte;

class ProduccionMedica extends Reporte
{
    public function clave(): string { return 'produccion-medica'; }

    public function titulo(): string { return 'Producción médica por profesional y servicio'; }

    public function modulo(): string { return 'Gestión Clínica y Consultas'; }

    public function permiso(): string { return 'reportes.clinicos'; }

    public function descripcion(): string
    {
        return 'Consultas realizadas por pediatra, desglosadas por tipo de servicio y período.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'doctor_id' => ['select', 'Profesional', $this->opcionesMedicos()],
            'servicio' => ['select', 'Servicio', Appointment::SERVICIOS],
        ];
    }

    public function columnas(): array
    {
        return ['Profesional', 'Consulta general', 'Control de niño sano', 'Telemedicina', 'Total consultas', 'Pacientes únicos'];
    }

    public function filas(array $f): array
    {
        return Consultation::with('doctor')
            ->whereBetween('fecha', [$this->desde($f), $this->hasta($f)])
            ->when($f['doctor_id'] ?? null, fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($f['servicio'] ?? null, fn ($q, $s) => $q->where('servicio', $s))
            ->get()->groupBy('doctor_id')
            ->map(fn ($c) => [
                $c->first()->doctor->name,
                $c->where('servicio', 'general')->count(),
                $c->where('servicio', 'control_nino_sano')->count(),
                $c->where('servicio', 'telemedicina')->count(),
                $c->count(),
                $c->pluck('patient_id')->unique()->count(),
            ])->sortByDesc(fn ($r) => $r[4])->values()->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);

        return ['Profesionales' => $c->count(), 'Total consultas' => $c->sum(4), 'Telemedicina' => $c->sum(3), 'Control de niño sano' => $c->sum(2)];
    }

    public function grafico(array $f, array $filas): ?array
    {
        return ['tipo' => 'bar', 'labels' => array_column($filas, 0), 'series' => [
            'General' => array_column($filas, 1), 'Niño sano' => array_column($filas, 2), 'Telemedicina' => array_column($filas, 3),
        ]];
    }
}
