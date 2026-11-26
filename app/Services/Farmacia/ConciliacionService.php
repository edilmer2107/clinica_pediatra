<?php

namespace App\Services\Farmacia;

use App\Exceptions\ReglaNegocioException;
use App\Models\InventoryCount;
use App\Models\Lot;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Conciliación de inventario: stock teórico vs. conteo físico.
 * Consistencia (%) = (1 - Σ|diferencias| / Σ stock teórico) × 100. Meta normativa: ≥ 99 %.
 */
class ConciliacionService
{
    public const META_CONSISTENCIA = 99.0;

    public function __construct(
        private readonly InventarioService $inventario,
        private readonly AuditService $audit,
    ) {}

    public function iniciar(): InventoryCount
    {
        return DB::transaction(function () {
            $conteo = InventoryCount::create(['fecha' => today(), 'estado' => 'borrador', 'user_id' => Auth::id()]);

            Lot::whereIn('estado', ['activo', 'cuarentena'])->where('cantidad_actual', '>', 0)
                ->orderBy('medication_id')->get()
                ->each(fn (Lot $l) => $conteo->items()->create([
                    'lot_id' => $l->id,
                    'stock_teorico' => $l->cantidad_actual,
                ]));

            $conteo->update(['stock_teorico_total' => $conteo->items()->sum('stock_teorico')]);

            return $conteo;
        });
    }

    public static function calcularConsistencia(int $teoricoTotal, int $sumaDiferenciasAbs): float
    {
        if ($teoricoTotal <= 0) {
            return 100.0;
        }

        return round(max(0, (1 - $sumaDiferenciasAbs / $teoricoTotal) * 100), 2);
    }

    /**
     * @param  array<int, int|string|null>  $fisicos  [item_id => stock físico]
     */
    public function registrarConteo(InventoryCount $conteo, array $fisicos, bool $cerrar, bool $ajustarStock): InventoryCount
    {
        if ($conteo->estado === 'cerrado') {
            throw new ReglaNegocioException('La conciliación ya está cerrada.');
        }

        return DB::transaction(function () use ($conteo, $fisicos, $cerrar, $ajustarStock) {
            foreach ($conteo->items as $item) {
                if (! array_key_exists($item->id, $fisicos) || $fisicos[$item->id] === null || $fisicos[$item->id] === '') {
                    continue;
                }
                $fisico = (int) $fisicos[$item->id];
                if ($fisico < 0) {
                    throw new ReglaNegocioException('El stock físico no puede ser negativo.');
                }
                $item->update(['stock_fisico' => $fisico, 'diferencia' => $fisico - $item->stock_teorico]);
            }

            $conteo->refresh()->load('items.lot');
            $contados = $conteo->items->whereNotNull('stock_fisico');

            if ($cerrar && $contados->count() !== $conteo->items->count()) {
                throw new ReglaNegocioException('Para cerrar la conciliación debe registrar el conteo físico de todos los lotes.');
            }

            $teorico = (int) $conteo->items->sum('stock_teorico');
            $sumaAbs = (int) $contados->sum(fn ($i) => abs($i->diferencia));

            $conteo->update([
                'stock_fisico_total' => (int) $contados->sum('stock_fisico'),
                'consistencia' => self::calcularConsistencia($teorico, $sumaAbs),
                'estado' => $cerrar ? 'cerrado' : 'borrador',
            ]);

            if ($cerrar && $ajustarStock) {
                foreach ($contados as $item) {
                    if ($item->diferencia !== 0) {
                        $this->inventario->ajustar($item->lot, $item->diferencia, "Ajuste por conciliación #{$conteo->id}", $conteo);
                    }
                }
            }

            if ($cerrar) {
                $this->audit->registrar('cierre_conciliacion', 'farmacia',
                    "Cierre de conciliación #{$conteo->id}: consistencia {$conteo->consistencia}%".($ajustarStock ? ' (stock ajustado)' : ''), $conteo);
            }

            return $conteo;
        });
    }
}
