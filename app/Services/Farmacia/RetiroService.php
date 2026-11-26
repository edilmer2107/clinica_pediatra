<?php

namespace App\Services\Farmacia;

use App\Exceptions\ReglaNegocioException;
use App\Models\DispensationItem;
use App\Models\Immunization;
use App\Models\Lot;
use App\Models\LotRecall;
use App\Services\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Trazabilidad inversa ante alertas sanitarias y retiro de lotes del mercado.
 */
class RetiroService
{
    public function __construct(
        private readonly InventarioService $inventario,
        private readonly AuditService $audit,
    ) {}

    /**
     * Pacientes y tutores que recibieron un lote (por dispensación o vacunación).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function trazabilidad(Lot $lote): Collection
    {
        $dispensados = DispensationItem::with(['dispensation.patient.tutors', 'dispensation.tutor'])
            ->where('lot_id', $lote->id)->get()
            ->map(fn (DispensationItem $i) => [
                'origen' => 'Dispensación '.$i->dispensation->codigo,
                'fecha' => $i->dispensation->fecha,
                'paciente' => $i->dispensation->patient,
                'tutor_retiro' => $i->dispensation->tutor,
                'tutores' => $i->dispensation->patient->tutors,
                'cantidad' => $i->cantidad,
            ]);

        $vacunados = Immunization::with(['patient.tutors'])
            ->where('lot_id', $lote->id)->get()
            ->map(fn (Immunization $i) => [
                'origen' => 'Vacunación',
                'fecha' => $i->fecha_aplicacion,
                'paciente' => $i->patient,
                'tutor_retiro' => null,
                'tutores' => $i->patient->tutors,
                'cantidad' => 1,
            ]);

        return $dispensados->concat($vacunados)->sortByDesc('fecha')->values();
    }

    public function retirar(Lot $lote, array $datos): LotRecall
    {
        if ($lote->estado === 'retirado') {
            throw new ReglaNegocioException("El lote {$lote->numero_lote} ya fue retirado.");
        }

        return DB::transaction(function () use ($lote, $datos) {
            $cantidad = $lote->cantidad_actual;

            $retiro = LotRecall::create([
                'lot_id' => $lote->id,
                'alerta_sanitaria' => $datos['alerta_sanitaria'],
                'motivo' => $datos['motivo'],
                'fecha' => $datos['fecha'] ?? today(),
                'cantidad_retirada' => $cantidad,
                'disposicion_final' => $datos['disposicion_final'],
                'detalle_disposicion' => $datos['detalle_disposicion'] ?? null,
                'registrado_por' => Auth::id(),
            ]);

            if ($cantidad > 0) {
                $this->inventario->ajustar($lote, -$cantidad, "Retiro por alerta {$datos['alerta_sanitaria']}", $retiro);
            }
            $lote->refresh()->update(['estado' => 'retirado']);

            $this->audit->registrar('retiro_lote', 'farmacia',
                "Retiro del lote {$lote->numero_lote} ({$lote->medication->nombre}) por alerta {$datos['alerta_sanitaria']}. Cantidad retirada: {$cantidad}. Disposición: {$datos['disposicion_final']}.",
                $retiro);

            return $retiro;
        });
    }
}
