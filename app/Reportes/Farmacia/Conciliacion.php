<?php

namespace App\Reportes\Farmacia;

use App\Models\InventoryCount;
use App\Reportes\Reporte;
use App\Services\Farmacia\ConciliacionService;

class Conciliacion extends Reporte
{
    public function clave(): string { return 'conciliacion'; }

    public function titulo(): string { return 'Conciliación de inventario (teórico vs. físico)'; }

    public function modulo(): string { return 'Farmacia e Inventario'; }

    public function permiso(): string { return 'reportes.farmacia'; }

    public function descripcion(): string
    {
        return 'Comparación del stock del sistema con el conteo físico. Meta de consistencia ≥ 99 %.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'conteo_id' => ['select', 'Conciliación', InventoryCount::latest()->get()->mapWithKeys(fn ($c) => [$c->id => "#{$c->id} - {$c->fecha->format('d/m/Y')} ({$c->estado})"])->all()],
        ];
    }

    public function columnas(): array
    {
        return ['Conciliación', 'Fecha', 'Medicamento', 'Lote', 'Ubicación', 'Stock teórico', 'Stock físico', 'Diferencia'];
    }

    public function filas(array $f): array
    {
        return InventoryCount::with('items.lot.medication')
            ->when($f['conteo_id'] ?? null, fn ($q, $id) => $q->whereKey($id),
                fn ($q) => $q->whereBetween('fecha', [$this->desde($f), $this->hasta($f)]))
            ->orderByDesc('id')->get()
            ->flatMap(fn (InventoryCount $c) => $c->items->map(fn ($i) => [
                '#'.$c->id,
                $c->fecha->format('d/m/Y'),
                $i->lot->medication->nombre.' '.$i->lot->medication->concentracion,
                $i->lot->numero_lote,
                $i->lot->ubicacion,
                $i->stock_teorico,
                $i->stock_fisico ?? 'Sin contar',
                $i->diferencia ?? '—',
            ]))->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $teorico = collect($filas)->sum(fn ($r) => $r[5]);
        $dif = collect($filas)->sum(fn ($r) => is_numeric($r[7]) ? abs($r[7]) : 0);
        $consistencia = ConciliacionService::calcularConsistencia((int) $teorico, (int) $dif);

        return [
            'Lotes' => count($filas),
            'Stock teórico' => $teorico,
            'Σ |diferencias|' => $dif,
            'Consistencia' => number_format($consistencia, 2).' % '.($consistencia >= ConciliacionService::META_CONSISTENCIA ? '(cumple)' : '(NO cumple)'),
        ];
    }
}
