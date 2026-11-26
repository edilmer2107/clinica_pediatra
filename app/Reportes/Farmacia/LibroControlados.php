<?php

namespace App\Reportes\Farmacia;

use App\Models\ControlledDrugEntry;
use App\Models\Medication;
use App\Reportes\Reporte;

class LibroControlados extends Reporte
{
    public function clave(): string { return 'libro-controlados'; }

    public function titulo(): string { return 'Libro oficial de estupefacientes y psicotrópicos'; }

    public function modulo(): string { return 'Farmacia e Inventario'; }

    public function permiso(): string { return 'controlados.gestionar'; }

    public function descripcion(): string
    {
        return 'Movimientos de entrada y salida con saldo acumulado, farmacéutico responsable y tutor que retira.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'medication_id' => ['select', 'Medicamento controlado', Medication::where('es_controlado', true)->orderBy('nombre')->get()
                ->mapWithKeys(fn ($m) => [$m->id => "{$m->nombre} {$m->concentracion} ({$m->tipo_control})"])->all()],
        ];
    }

    protected function desdePorDefecto(): string
    {
        return today()->startOfYear()->toDateString();
    }

    public function columnas(): array
    {
        return ['N°', 'Fecha', 'Medicamento', 'Lote', 'Documento', 'Entrada', 'Salida', 'Saldo', 'Q.F. responsable', 'Retira (DNI)', 'Paciente'];
    }

    public function filas(array $f): array
    {
        return ControlledDrugEntry::with(['medication', 'lot', 'pharmacist', 'tutor', 'patient'])
            ->whereBetween('fecha', [$this->desde($f), $this->hasta($f)])
            ->when($f['medication_id'] ?? null, fn ($q, $id) => $q->where('medication_id', $id))
            ->orderBy('medication_id')->orderBy('id')->get()
            ->map(fn (ControlledDrugEntry $e) => [
                $e->id,
                $e->fecha->format('d/m/Y H:i'),
                $e->medication->nombre.' '.$e->medication->concentracion,
                $e->lot->numero_lote,
                $e->documento,
                $e->tipo === 'entrada' ? $e->cantidad : '',
                $e->tipo === 'salida' ? $e->cantidad : '',
                $e->saldo,
                $e->pharmacist->name.' '.$e->pharmacist->colegiatura,
                $e->tutor ? $e->tutor->nombre_completo.' ('.$e->tutor_dni.')' : ($e->tutor_dni ?? ''),
                $e->patient?->nombre_completo ?? '',
            ])->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);

        return [
            'Movimientos' => $c->count(),
            'Total entradas' => $c->sum(fn ($r) => (int) $r[5]),
            'Total salidas' => $c->sum(fn ($r) => (int) $r[6]),
        ];
    }
}
