<?php

namespace App\Services\Facturacion;

use App\Models\Charge;
use App\Models\Patient;
use App\Models\PatientAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Cuenta única por paciente: consultas, procedimientos, vacunas, exámenes y
 * medicamentos se acumulan en la misma cuenta abierta hasta que se factura.
 */
class CuentaService
{
    public function cuentaAbierta(Patient $paciente): PatientAccount
    {
        return PatientAccount::firstOrCreate(
            ['patient_id' => $paciente->id, 'estado' => 'abierta'],
            ['abierta_at' => now()],
        );
    }

    public function agregarCargo(Patient $paciente, string $tipo, string $descripcion, float $cantidad, float $precioUnitario, ?Model $origen = null): Charge
    {
        $cuenta = $this->cuentaAbierta($paciente);

        return Charge::create([
            'patient_account_id' => $cuenta->id,
            'patient_id' => $paciente->id,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => round($cantidad * $precioUnitario, 2),
            'origen_type' => $origen?->getMorphClass(),
            'origen_id' => $origen?->getKey(),
            'creado_por' => Auth::id(),
        ]);
    }
}
