<?php

namespace App\Services\Farmacia;

use App\Exceptions\ReglaNegocioException;
use App\Models\Dispensation;
use App\Models\Lot;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Tutor;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Facturacion\CuentaService;
use Illuminate\Support\Facades\DB;

/**
 * Dispensación asociada a receta con validación farmacéutica:
 * - El medicamento dispensado debe ser exactamente el prescrito.
 * - La cantidad no puede exceder lo pendiente de la receta.
 * - Solo se usan lotes vigentes (no vencidos, ni en cuarentena, ni retirados).
 * - Los controlados exigen tutor identificado y quedan en el libro oficial.
 */
class DispensacionService
{
    public function __construct(
        private readonly InventarioService $inventario,
        private readonly ControladosService $controlados,
        private readonly CuentaService $cuentas,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<int, array{prescription_item_id:int, medication_id:int, lot_id:int, cantidad:int}>  $lineas
     */
    public function dispensar(Prescription $receta, array $lineas, User $farmaceutico, ?int $tutorId, ?string $observaciones = null): Dispensation
    {
        if (in_array($receta->estado, ['anulada', 'dispensada'], true)) {
            throw new ReglaNegocioException("La receta {$receta->codigo} está {$receta->estado} y no admite dispensación.");
        }

        $lineas = array_values(array_filter($lineas, fn ($l) => (int) ($l['cantidad'] ?? 0) > 0));
        if ($lineas === []) {
            throw new ReglaNegocioException('Debe indicar al menos un medicamento con cantidad mayor a cero.');
        }

        $tutor = $tutorId ? Tutor::find($tutorId) : null;
        if ($tutor && ! $receta->patient->tutors()->whereKey($tutor->id)->exists()) {
            throw new ReglaNegocioException('El tutor que retira no está vinculado al paciente.');
        }

        return DB::transaction(function () use ($receta, $lineas, $farmaceutico, $tutor, $observaciones) {
            $dispensacion = Dispensation::create([
                'codigo' => Dispensation::siguienteCodigo(),
                'prescription_id' => $receta->id,
                'patient_id' => $receta->patient_id,
                'pharmacist_id' => $farmaceutico->id,
                'tutor_id' => $tutor?->id,
                'fecha' => now(),
                'observaciones' => $observaciones,
            ]);

            foreach ($lineas as $linea) {
                $this->procesarLinea($receta, $dispensacion, $linea, $farmaceutico, $tutor);
            }

            $receta->refresh()->load('items');
            $pendiente = $receta->items->sum(fn (PrescriptionItem $i) => $i->cantidadPendiente());
            $receta->update(['estado' => $pendiente === 0 ? 'dispensada' : 'parcial']);

            return $dispensacion->load('items.medication', 'items.lot');
        });
    }

    private function procesarLinea(Prescription $receta, Dispensation $dispensacion, array $linea, User $farmaceutico, ?Tutor $tutor): void
    {
        /** @var PrescriptionItem|null $item */
        $item = PrescriptionItem::query()->lockForUpdate()
            ->where('prescription_id', $receta->id)
            ->find($linea['prescription_item_id']);

        if (! $item) {
            throw new ReglaNegocioException('El ítem indicado no pertenece a la receta.');
        }

        $cantidad = (int) $linea['cantidad'];
        $medicamento = $item->medication;

        if ((int) $linea['medication_id'] !== $item->medication_id) {
            throw new ReglaNegocioException("El medicamento dispensado no corresponde al prescrito ({$medicamento->nombre}).");
        }
        if ($cantidad > $item->cantidadPendiente()) {
            throw new ReglaNegocioException("La cantidad de {$medicamento->nombre} ({$cantidad}) excede lo pendiente en la receta ({$item->cantidadPendiente()}).");
        }

        $lote = Lot::find($linea['lot_id']);
        if (! $lote || $lote->medication_id !== $medicamento->id) {
            throw new ReglaNegocioException("El lote seleccionado no corresponde a {$medicamento->nombre}.");
        }

        if ($medicamento->es_controlado && ! $tutor) {
            throw new ReglaNegocioException("{$medicamento->nombre} es controlado: debe identificar al tutor que retira.");
        }

        $this->inventario->descontar($lote, $cantidad, 'dispensacion', $dispensacion, "Receta {$receta->codigo}");

        $dispensacion->items()->create([
            'prescription_item_id' => $item->id,
            'medication_id' => $medicamento->id,
            'lot_id' => $lote->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $medicamento->precio_venta,
        ]);

        $item->increment('cantidad_dispensada', $cantidad);

        $this->cuentas->agregarCargo(
            $receta->patient,
            'medicamento',
            "{$medicamento->nombre_completo} - Lote {$lote->numero_lote}",
            $cantidad,
            $medicamento->precio_venta,
            $dispensacion,
        );

        if ($medicamento->es_controlado) {
            $this->controlados->registrar($medicamento, $lote, 'salida', $cantidad, $farmaceutico->id, [
                'tutor_id' => $tutor->id,
                'tutor_dni' => $tutor->dni,
                'patient_id' => $receta->patient_id,
                'prescription_id' => $receta->id,
                'documento' => "Receta {$receta->codigo}",
            ]);

            $this->audit->registrar(
                'dispensacion_controlado',
                'farmacia',
                "Dispensación de controlado {$medicamento->nombre} x{$cantidad} (lote {$lote->numero_lote}) a {$tutor->nombre_completo} DNI {$tutor->dni}, receta {$receta->codigo}.",
                $dispensacion,
                null,
                ['medication_id' => $medicamento->id, 'lot_id' => $lote->id, 'cantidad' => $cantidad, 'tutor_dni' => $tutor->dni],
            );
        }
    }
}
