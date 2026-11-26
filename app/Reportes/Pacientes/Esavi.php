<?php

namespace App\Reportes\Pacientes;

use App\Models\EsaviReport;
use App\Models\Vaccine;
use App\Reportes\Reporte;

class Esavi extends Reporte
{
    public function clave(): string { return 'esavi'; }

    public function titulo(): string { return 'Eventos supuestamente atribuidos a la vacunación (ESAVI)'; }

    public function modulo(): string { return 'Pacientes Pediátricos'; }

    public function permiso(): string { return 'reportes.clinicos'; }

    public function descripcion(): string
    {
        return 'Datos del paciente, vacuna, lote aplicado y síntomas, para notificación a la autoridad sanitaria.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'vaccine_id' => ['select', 'Vacuna', Vaccine::orderBy('nombre')->pluck('nombre', 'id')->all()],
            'gravedad' => ['select', 'Gravedad', ['leve' => 'Leve', 'moderado' => 'Moderado', 'grave' => 'Grave']],
        ];
    }

    public function columnas(): array
    {
        return ['N°', 'HC', 'Paciente', 'Sexo', 'Fecha nac.', 'Vacuna', 'Dosis', 'Lote', 'Fecha vacunación', 'Inicio síntomas', 'Síntomas', 'Gravedad', 'Desenlace'];
    }

    public function filas(array $f): array
    {
        return EsaviReport::with(['patient', 'immunization.vaccine', 'immunization.lot', 'immunization.scheduleDose'])
            ->whereBetween('fecha_inicio_sintomas', [$this->desde($f), $this->hasta($f)])
            ->when($f['gravedad'] ?? null, fn ($q, $g) => $q->where('gravedad', $g))
            ->when($f['vaccine_id'] ?? null, fn ($q, $v) => $q->whereHas('immunization', fn ($i) => $i->where('vaccine_id', $v)))
            ->orderByDesc('fecha_inicio_sintomas')->get()
            ->map(fn (EsaviReport $e) => [
                $e->id, $e->patient->historia_clinica, $e->patient->nombre_completo, $e->patient->sexo,
                $e->patient->fecha_nacimiento->format('d/m/Y'), $e->immunization->vaccine->nombre,
                $e->immunization->scheduleDose?->nombre_dosis, $e->immunization->lot->numero_lote,
                $e->immunization->fecha_aplicacion->format('d/m/Y'), $e->fecha_inicio_sintomas->format('d/m/Y H:i'),
                $e->sintomas, ucfirst($e->gravedad), str_replace('_', ' ', (string) $e->desenlace),
            ])->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);

        return ['ESAVI notificados' => $c->count(), 'Graves' => $c->where(11, 'Grave')->count(), 'Lotes involucrados' => $c->pluck(7)->unique()->count()];
    }
}
