<?php

namespace App\Reportes\Facturacion;

use App\Models\Invoice;
use App\Reportes\Reporte;

class TicketPromedio extends Reporte
{
    public function clave(): string { return 'ticket-promedio'; }

    public function titulo(): string { return 'Ticket promedio por paciente'; }

    public function modulo(): string { return 'Facturación y Administración'; }

    public function permiso(): string { return 'reportes.financieros'; }

    public function descripcion(): string
    {
        return 'Gasto promedio (consulta + farmacia) por paciente atendido, por mes.';
    }

    public function filtros(): array
    {
        return parent::filtros() + ['tipo_paciente' => ['select', 'Tipo de paciente', self::gruposEdad()]];
    }

    public function columnas(): array
    {
        return ['Mes', 'Pacientes facturados', 'Comprobantes', 'Servicios (S/)', 'Farmacia (S/)', 'Total (S/)', 'Ticket promedio por paciente (S/)', 'Promedio servicios (S/)', 'Promedio farmacia (S/)'];
    }

    public function filas(array $f): array
    {
        return Invoice::with('patient')->where('estado', '!=', 'anulada')
            ->whereBetween('fecha_emision', [$this->desde($f), $this->hasta($f)])->get()
            ->filter(fn ($i) => empty($f['tipo_paciente']) || self::grupoDeEdad($i->patient->fecha_nacimiento, $i->fecha_emision) === $f['tipo_paciente'])
            ->groupBy(fn ($i) => $i->fecha_emision->format('Y-m'))->sortKeys()
            ->map(function ($g, $mes) {
                $pacientes = max(1, $g->pluck('patient_id')->unique()->count());

                return [
                    $mes, $g->pluck('patient_id')->unique()->count(), $g->count(),
                    number_format($g->sum('subtotal_servicios'), 2, '.', ''), number_format($g->sum('subtotal_medicamentos'), 2, '.', ''),
                    number_format($g->sum('total'), 2, '.', ''), number_format($g->sum('total') / $pacientes, 2, '.', ''),
                    number_format($g->sum('subtotal_servicios') / $pacientes, 2, '.', ''), number_format($g->sum('subtotal_medicamentos') / $pacientes, 2, '.', ''),
                ];
            })->values()->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);
        $pac = max(1, $c->sum(1));

        return ['Pacientes' => $c->sum(1), 'Total (S/)' => number_format($c->sum(5), 2), 'Ticket promedio global (S/)' => number_format($c->sum(5) / $pac, 2)];
    }
}
