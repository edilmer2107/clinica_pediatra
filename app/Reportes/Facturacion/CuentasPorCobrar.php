<?php

namespace App\Reportes\Facturacion;

use App\Models\Invoice;
use App\Reportes\Reporte;

class CuentasPorCobrar extends Reporte
{
    public function clave(): string { return 'cuentas-por-cobrar'; }

    public function titulo(): string { return 'Cuentas por cobrar y antigüedad de saldos'; }

    public function modulo(): string { return 'Facturación y Administración'; }

    public function permiso(): string { return 'reportes.financieros'; }

    public function descripcion(): string
    {
        return 'Comprobantes con saldo pendiente clasificados por antigüedad (0-30, 31-60, 61-90 y más de 90 días).';
    }

    public function filtros(): array
    {
        return ['tramo' => ['select', 'Antigüedad', self::TRAMOS]];
    }

    public const TRAMOS = ['0-30' => '0 a 30 días', '31-60' => '31 a 60 días', '61-90' => '61 a 90 días', '90+' => 'Más de 90 días'];

    public static function tramo(int $dias): string
    {
        return match (true) {
            $dias <= 30 => '0-30',
            $dias <= 60 => '31-60',
            $dias <= 90 => '61-90',
            default => '90+',
        };
    }

    protected function desdePorDefecto(): string
    {
        return today()->subYear()->toDateString();
    }

    public function columnas(): array
    {
        return ['Comprobante', 'Emisión', 'Paciente', 'Cliente (tutor)', 'Celular', 'Total (S/)', 'Pagado (S/)', 'Saldo (S/)', 'Días', 'Tramo'];
    }

    public function filas(array $f): array
    {
        return Invoice::with(['patient', 'tutor'])->whereIn('estado', ['pendiente', 'parcial'])->orderBy('fecha_emision')->get()
            ->map(function (Invoice $i) {
                $dias = (int) $i->fecha_emision->diffInDays(today());

                return [$i->numero, $i->fecha_emision->format('d/m/Y'), $i->patient->nombre_completo, $i->tutor?->nombre_completo, $i->tutor?->telefono,
                    number_format($i->total, 2, '.', ''), number_format($i->monto_pagado, 2, '.', ''), number_format($i->saldo(), 2, '.', ''), $dias, self::TRAMOS[self::tramo($dias)]];
            })
            ->filter(fn ($r) => empty($f['tramo']) || $r[9] === self::TRAMOS[$f['tramo']])
            ->values()->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);
        $res = ['Saldo total (S/)' => number_format($c->sum(7), 2)];
        foreach (self::TRAMOS as $etiqueta) {
            $res[$etiqueta] = 'S/ '.number_format($c->where(9, $etiqueta)->sum(7), 2);
        }

        return $res;
    }
}
