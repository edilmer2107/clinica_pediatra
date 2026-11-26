<?php

namespace App\Reportes\Farmacia;

use App\Models\Lot;
use App\Models\Medication;
use App\Reportes\Reporte;

class Vencimientos extends Reporte
{
    public function clave(): string { return 'vencimientos'; }

    public function titulo(): string { return 'Vencimientos y lotes críticos'; }

    public function modulo(): string { return 'Farmacia e Inventario'; }

    public function permiso(): string { return 'reportes.farmacia'; }

    public function descripcion(): string
    {
        return 'Lotes con stock vencidos o próximos a vencer, con ubicación exacta en almacén.';
    }

    public function filtros(): array
    {
        return [
            'dias' => ['number', 'Vencen en (días)'],
            'medication_id' => ['select', 'Medicamento', Medication::orderBy('nombre')->pluck('nombre', 'id')->all()],
        ];
    }

    public function valoresFiltros(\Illuminate\Http\Request $request): array
    {
        $f = parent::valoresFiltros($request);
        $f['dias'] = max(1, min(730, (int) ($f['dias'] ?: config('clinica.dias_alerta_vencimiento'))));
        $f['desde'] = today()->toDateString();
        $f['hasta'] = today()->addDays($f['dias'])->toDateString();

        return $f;
    }

    public function columnas(): array
    {
        return ['Medicamento', 'Lote', 'Vencimiento', 'Días restantes', 'Stock', 'Almacén', 'Ubicación', 'Estado', 'Situación'];
    }

    public function filas(array $f): array
    {
        return Lot::with('medication')
            ->where('cantidad_actual', '>', 0)
            ->whereIn('estado', ['activo', 'cuarentena'])
            ->whereDate('fecha_vencimiento', '<=', today()->addDays($f['dias']))
            ->when($f['medication_id'] ?? null, fn ($q, $id) => $q->where('medication_id', $id))
            ->orderBy('fecha_vencimiento')
            ->get()
            ->map(fn (Lot $l) => [
                $l->medication->nombre.' '.$l->medication->concentracion,
                $l->numero_lote,
                $l->fecha_vencimiento->format('d/m/Y'),
                $l->diasParaVencer(),
                $l->cantidad_actual,
                $l->almacen,
                $l->ubicacion,
                Lot::ESTADOS[$l->estado],
                $l->estaVencido() ? 'VENCIDO - bloqueado' : ($l->diasParaVencer() <= 30 ? 'Crítico (≤ 30 d)' : 'Próximo a vencer'),
            ])->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $vencidos = collect($filas)->filter(fn ($r) => $r[3] < 0)->count();

        return [
            'Lotes listados' => count($filas),
            'Vencidos con stock' => $vencidos,
            'Unidades comprometidas' => collect($filas)->sum(fn ($r) => $r[4]),
            'Horizonte' => $f['dias'].' días',
        ];
    }
}
