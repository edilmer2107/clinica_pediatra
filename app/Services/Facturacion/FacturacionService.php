<?php

namespace App\Services\Facturacion;

use App\Exceptions\ReglaNegocioException;
use App\Models\Charge;
use App\Models\Invoice;
use App\Models\PatientAccount;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Facturación consolidada (cuenta única): una sola factura por cuenta con
 * consultas, procedimientos, vacunas, exámenes y medicamentos dispensados.
 * Los precios incluyen IGV (18 %).
 */
class FacturacionService
{
    public function __construct(private readonly AuditService $audit) {}

    /** @return array{servicios: float, medicamentos: float, total: float, subtotal: float, igv: float} */
    public static function calcularTotales(iterable $cargos): array
    {
        $servicios = 0.0;
        $medicamentos = 0.0;
        foreach ($cargos as $c) {
            if ($c->tipo === 'medicamento') {
                $medicamentos += (float) $c->subtotal;
            } else {
                $servicios += (float) $c->subtotal;
            }
        }
        $total = round($servicios + $medicamentos, 2);
        $subtotal = round($total / (1 + config('clinica.igv')), 2);

        return [
            'servicios' => round($servicios, 2),
            'medicamentos' => round($medicamentos, 2),
            'total' => $total,
            'subtotal' => $subtotal,
            'igv' => round($total - $subtotal, 2),
        ];
    }

    public function emitir(PatientAccount $cuenta, User $usuario, ?int $tutorId = null): Invoice
    {
        if ($cuenta->estado !== 'abierta') {
            throw new ReglaNegocioException('La cuenta ya fue facturada.');
        }

        return DB::transaction(function () use ($cuenta, $usuario, $tutorId) {
            $cargos = Charge::where('patient_account_id', $cuenta->id)->whereNull('invoice_id')->lockForUpdate()->get();
            if ($cargos->isEmpty()) {
                throw new ReglaNegocioException('La cuenta no tiene cargos pendientes de facturar.');
            }
            $t = self::calcularTotales($cargos);

            $factura = Invoice::create([
                'patient_account_id' => $cuenta->id,
                'patient_id' => $cuenta->patient_id,
                'tutor_id' => $tutorId ?? $cuenta->patient->tutorPrincipal()?->id,
                'fecha_emision' => today(),
                'fecha_vencimiento' => today()->addDays(config('clinica.dias_credito')),
                'subtotal_servicios' => $t['servicios'],
                'subtotal_medicamentos' => $t['medicamentos'],
                'subtotal' => $t['subtotal'],
                'igv' => $t['igv'],
                'total' => $t['total'],
                'monto_pagado' => 0,
                'estado' => 'pendiente',
                'emitida_por' => $usuario->id,
            ]);

            Charge::whereIn('id', $cargos->pluck('id'))->update(['invoice_id' => $factura->id]);
            $cuenta->update(['estado' => 'facturada', 'cerrada_at' => now()]);

            $this->audit->registrar('emitir_factura', 'facturacion', "Emisión de {$factura->numero} por S/ {$factura->total} ({$cargos->count()} cargos).", $factura);

            return $factura;
        });
    }

    public function registrarPago(Invoice $factura, float $monto, string $metodo, ?string $referencia, User $usuario): Payment
    {
        if ($factura->estado === 'anulada') {
            throw new ReglaNegocioException('No se pueden registrar pagos en una factura anulada.');
        }
        if ($monto <= 0) {
            throw new ReglaNegocioException('El monto del pago debe ser mayor a cero.');
        }

        return DB::transaction(function () use ($factura, $monto, $metodo, $referencia, $usuario) {
            $factura = Invoice::lockForUpdate()->findOrFail($factura->id);
            if (round($monto, 2) > $factura->saldo()) {
                throw new ReglaNegocioException('El monto (S/ '.number_format($monto, 2).') excede el saldo pendiente (S/ '.number_format($factura->saldo(), 2).').');
            }

            $pago = $factura->payments()->create([
                'monto' => round($monto, 2), 'metodo' => $metodo, 'referencia' => $referencia, 'fecha' => now(), 'user_id' => $usuario->id,
            ]);

            $pagado = round($factura->monto_pagado + $monto, 2);
            $factura->update(['monto_pagado' => $pagado, 'estado' => $pagado >= $factura->total ? 'pagada' : 'parcial']);
            $this->audit->registrar('registrar_pago', 'facturacion', "Pago de S/ {$pago->monto} ({$metodo}) a {$factura->numero}.", $pago);

            return $pago;
        });
    }

    public function anular(Invoice $factura, string $motivo, User $usuario): void
    {
        if ($factura->estado === 'anulada') {
            throw new ReglaNegocioException('La factura ya está anulada.');
        }
        if ($factura->monto_pagado > 0) {
            throw new ReglaNegocioException('No se puede anular una factura con pagos registrados; emita primero la devolución.');
        }

        DB::transaction(function () use ($factura, $motivo, $usuario) {
            $antes = $factura->only(['estado', 'total']);
            $factura->update(['estado' => 'anulada', 'motivo_anulacion' => $motivo, 'anulada_por' => $usuario->id, 'anulada_at' => now()]);

            // Los cargos vuelven a la cuenta abierta del paciente para refacturarse
            $abierta = PatientAccount::firstOrCreate(['patient_id' => $factura->patient_id, 'estado' => 'abierta'], ['abierta_at' => now()]);
            Charge::where('invoice_id', $factura->id)->update(['invoice_id' => null, 'patient_account_id' => $abierta->id]);

            $this->audit->registrar('anular_factura', 'facturacion', "Anulación de {$factura->numero} (S/ {$factura->total}). Motivo: {$motivo}", $factura, $antes, ['estado' => 'anulada']);
        });
    }
}
