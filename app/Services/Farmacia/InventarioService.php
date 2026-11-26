<?php

namespace App\Services\Farmacia;

use App\Exceptions\ReglaNegocioException;
use App\Models\InventoryMovement;
use App\Models\Lot;
use App\Models\Medication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Gestión de stock por lote con criterio FEFO (First Expired, First Out).
 */
class InventarioService
{
    /**
     * Lotes dispensables ordenados por FEFO: vencimiento más próximo primero.
     * Excluye lotes vencidos, en cuarentena, retirados o sin stock.
     */
    public function lotesFefo(Medication $medicamento): Collection
    {
        return $medicamento->lots()
            ->where('estado', 'activo')
            ->where('cantidad_actual', '>', 0)
            ->whereDate('fecha_vencimiento', '>=', today())
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->get();
    }

    /** Lote sugerido por FEFO (o null si no hay stock dispensable). */
    public function sugerirLote(Medication $medicamento): ?Lot
    {
        return $this->lotesFefo($medicamento)->first();
    }

    /**
     * Propone la distribución de una cantidad entre lotes según FEFO.
     *
     * @return array<int, array{lot: Lot, cantidad: int}>
     */
    public function distribuirFefo(Medication $medicamento, int $cantidad): array
    {
        $restante = $cantidad;
        $plan = [];

        foreach ($this->lotesFefo($medicamento) as $lote) {
            if ($restante <= 0) {
                break;
            }
            $tomar = min($restante, $lote->cantidad_actual);
            $plan[] = ['lot' => $lote, 'cantidad' => $tomar];
            $restante -= $tomar;
        }

        if ($restante > 0) {
            throw new ReglaNegocioException(
                "Stock insuficiente de {$medicamento->nombre}: se requieren {$cantidad} y hay ".($cantidad - $restante).' disponibles en lotes vigentes.'
            );
        }

        return $plan;
    }

    /** Registra el ingreso de un lote nuevo. */
    public function registrarEntrada(array $datos): Lot
    {
        return DB::transaction(function () use ($datos) {
            $lote = Lot::create([
                'medication_id' => $datos['medication_id'],
                'numero_lote' => $datos['numero_lote'],
                'fecha_vencimiento' => $datos['fecha_vencimiento'],
                'cantidad_inicial' => $datos['cantidad'],
                'cantidad_actual' => $datos['cantidad'],
                'costo_unitario' => $datos['costo_unitario'] ?? 0,
                'estado' => $datos['estado'] ?? 'activo',
                'almacen' => $datos['almacen'] ?? 'Almacén principal',
                'ubicacion' => $datos['ubicacion'] ?? null,
                'proveedor' => $datos['proveedor'] ?? null,
                'fecha_ingreso' => $datos['fecha_ingreso'] ?? today(),
            ]);

            $this->registrarMovimiento($lote, 'entrada', (int) $datos['cantidad'], null, 'Ingreso de lote');

            return $lote;
        });
    }

    /**
     * Descuenta unidades de un lote validando que sea dispensable.
     * Debe llamarse dentro de una transacción; bloquea la fila del lote.
     */
    public function descontar(Lot $lote, int $cantidad, string $tipo, ?Model $referencia = null, ?string $motivo = null): Lot
    {
        if ($cantidad <= 0) {
            throw new ReglaNegocioException('La cantidad a descontar debe ser mayor a cero.');
        }

        /** @var Lot $lote */
        $lote = Lot::query()->lockForUpdate()->findOrFail($lote->id);

        if ($lote->estado === 'cuarentena') {
            throw new ReglaNegocioException("El lote {$lote->numero_lote} está en cuarentena y no puede usarse.");
        }
        if ($lote->estado === 'retirado') {
            throw new ReglaNegocioException("El lote {$lote->numero_lote} fue retirado del mercado.");
        }
        if ($lote->estaVencido()) {
            throw new ReglaNegocioException("El lote {$lote->numero_lote} está vencido ({$lote->fecha_vencimiento->format('d/m/Y')}).");
        }
        if ($lote->cantidad_actual < $cantidad) {
            throw new ReglaNegocioException("Stock insuficiente en el lote {$lote->numero_lote}: disponible {$lote->cantidad_actual}, solicitado {$cantidad}.");
        }

        $lote->cantidad_actual -= $cantidad;
        if ($lote->cantidad_actual === 0) {
            $lote->estado = 'agotado';
        }
        $lote->save();

        $this->registrarMovimiento($lote, $tipo, -$cantidad, $referencia, $motivo);

        return $lote;
    }

    /** Ajuste manual de stock (positivo o negativo), p. ej. tras conciliación. */
    public function ajustar(Lot $lote, int $diferencia, string $motivo, ?Model $referencia = null): void
    {
        if ($diferencia === 0) {
            return;
        }

        $lote = Lot::query()->lockForUpdate()->findOrFail($lote->id);
        $nuevo = $lote->cantidad_actual + $diferencia;
        if ($nuevo < 0) {
            throw new ReglaNegocioException("El ajuste dejaría stock negativo en el lote {$lote->numero_lote}.");
        }
        $lote->cantidad_actual = $nuevo;
        if ($nuevo === 0 && $lote->estado === 'activo') {
            $lote->estado = 'agotado';
        } elseif ($nuevo > 0 && $lote->estado === 'agotado') {
            $lote->estado = 'activo';
        }
        $lote->save();

        $this->registrarMovimiento($lote, 'ajuste', $diferencia, $referencia, $motivo);
    }

    public function cambiarEstado(Lot $lote, string $estado): void
    {
        if (! array_key_exists($estado, Lot::ESTADOS)) {
            throw new ReglaNegocioException('Estado de lote no válido.');
        }
        if ($lote->estado === 'retirado') {
            throw new ReglaNegocioException('Un lote retirado del mercado no puede cambiar de estado.');
        }
        $lote->update(['estado' => $estado]);
    }

    private function registrarMovimiento(Lot $lote, string $tipo, int $cantidad, ?Model $referencia, ?string $motivo): void
    {
        InventoryMovement::create([
            'medication_id' => $lote->medication_id,
            'lot_id' => $lote->id,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'saldo_lote' => $lote->cantidad_actual,
            'referencia_type' => $referencia?->getMorphClass(),
            'referencia_id' => $referencia?->getKey(),
            'motivo' => $motivo,
            'user_id' => Auth::id(),
        ]);
    }
}
