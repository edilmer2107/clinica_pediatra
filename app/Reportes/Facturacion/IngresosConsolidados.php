<?php

namespace App\Reportes\Facturacion;

use App\Models\Invoice;
use App\Models\Payment;
use App\Reportes\Reporte;

class IngresosConsolidados extends Reporte
{
    public function clave(): string { return 'ingresos-consolidados'; }

    public function titulo(): string { return 'Ingresos consolidados (clínica + farmacia)'; }

    public function modulo(): string { return 'Facturación y Administración'; }

    public function permiso(): string { return 'reportes.financieros'; }

    public function descripcion(): string
    {
        return 'Total facturado y cobrado por período, diferenciando servicios médicos de venta de medicamentos.';
    }

    public function filtros(): array
    {
        return parent::filtros() + ['agrupar' => ['select', 'Agrupar por', ['dia' => 'Día', 'mes' => 'Mes']]];
    }

    public function columnas(): array
    {
        return ['Período', 'Comprobantes', 'Servicios médicos (S/)', 'Medicamentos (S/)', 'Total facturado (S/)', 'IGV (S/)', 'Cobrado (S/)', 'Por cobrar (S/)'];
    }

    public function filas(array $f): array
    {
        $formato = ($f['agrupar'] ?? 'dia') === 'mes' ? 'Y-m' : 'Y-m-d';
        $facturas = Invoice::where('estado', '!=', 'anulada')->whereBetween('fecha_emision', [$this->desde($f), $this->hasta($f)])->get();
        $pagos = Payment::whereHas('invoice', fn ($q) => $q->where('estado', '!=', 'anulada'))
            ->whereBetween('fecha', [$this->desde($f), $this->hasta($f)])->get()
            ->groupBy(fn ($p) => $p->fecha->format($formato));

        return $facturas->groupBy(fn ($i) => $i->fecha_emision->format($formato))->sortKeys()
            ->map(fn ($g, $periodo) => [
                $periodo, $g->count(),
                number_format($g->sum('subtotal_servicios'), 2, '.', ''),
                number_format($g->sum('subtotal_medicamentos'), 2, '.', ''),
                number_format($g->sum('total'), 2, '.', ''),
                number_format($g->sum('igv'), 2, '.', ''),
                number_format(($pagos[$periodo] ?? collect())->sum('monto'), 2, '.', ''),
                number_format($g->sum(fn ($i) => $i->saldo()), 2, '.', ''),
            ])->values()->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);

        return [
            'Servicios médicos' => 'S/ '.number_format($c->sum(2), 2),
            'Medicamentos' => 'S/ '.number_format($c->sum(3), 2),
            'Total facturado' => 'S/ '.number_format($c->sum(4), 2),
            'Total cobrado' => 'S/ '.number_format($c->sum(6), 2),
        ];
    }

    public function grafico(array $f, array $filas): ?array
    {
        return ['tipo' => 'bar', 'labels' => array_column($filas, 0), 'series' => [
            'Servicios médicos' => array_map('floatval', array_column($filas, 2)), 'Medicamentos' => array_map('floatval', array_column($filas, 3)),
        ]];
    }
}
