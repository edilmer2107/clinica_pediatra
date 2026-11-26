<?php

namespace App\Services\Farmacia;

use App\Exceptions\ReglaNegocioException;
use App\Models\ControlledDrugEntry;
use App\Models\Lot;
use App\Models\Medication;

/**
 * Libro de estupefacientes y psicotrópicos con saldo acumulado por medicamento.
 */
class ControladosService
{
    public function saldoActual(Medication $medicamento): int
    {
        return (int) (ControlledDrugEntry::where('medication_id', $medicamento->id)
            ->orderByDesc('id')->value('saldo') ?? 0);
    }

    /**
     * Registra un movimiento en el libro. Exige farmacéutico responsable y,
     * en salidas, la identificación (DNI) del tutor que retira.
     */
    public function registrar(Medication $medicamento, Lot $lote, string $tipo, int $cantidad, int $farmaceuticoId, array $extra = []): ControlledDrugEntry
    {
        if (! $medicamento->es_controlado) {
            throw new ReglaNegocioException("{$medicamento->nombre} no es un medicamento controlado.");
        }
        if (! in_array($tipo, ['entrada', 'salida'], true)) {
            throw new ReglaNegocioException('Tipo de movimiento no válido.');
        }
        if ($cantidad <= 0) {
            throw new ReglaNegocioException('La cantidad debe ser mayor a cero.');
        }
        if ($tipo === 'salida' && empty($extra['tutor_dni'])) {
            throw new ReglaNegocioException('Para la salida de un medicamento controlado es obligatorio identificar al tutor que retira (DNI).');
        }

        $saldoAnterior = $this->saldoActual($medicamento);
        $saldo = $tipo === 'entrada' ? $saldoAnterior + $cantidad : $saldoAnterior - $cantidad;

        if ($saldo < 0) {
            throw new ReglaNegocioException("Saldo insuficiente en el libro de controlados para {$medicamento->nombre} (saldo {$saldoAnterior}).");
        }

        return ControlledDrugEntry::create([
            'medication_id' => $medicamento->id,
            'lot_id' => $lote->id,
            'fecha' => now(),
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'saldo' => $saldo,
            'documento' => $extra['documento'] ?? null,
            'pharmacist_id' => $farmaceuticoId,
            'tutor_id' => $extra['tutor_id'] ?? null,
            'tutor_dni' => $extra['tutor_dni'] ?? null,
            'patient_id' => $extra['patient_id'] ?? null,
            'prescription_id' => $extra['prescription_id'] ?? null,
            'observaciones' => $extra['observaciones'] ?? null,
        ]);
    }
}
