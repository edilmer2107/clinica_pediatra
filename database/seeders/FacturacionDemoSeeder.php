<?php

namespace Database\Seeders;

use App\Models\DocumentAccess;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\NotificationLog;
use App\Models\PatientAccount;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Facturacion\FacturacionService;
use Illuminate\Database\Seeder;

class FacturacionDemoSeeder extends Seeder
{
    public function run(FacturacionService $facturacion): void
    {
        if (Invoice::exists()) {
            return;
        }
        mt_srand(99);
        $caja = User::where('email', 'facturacion@clinica.test')->first();
        $admin = User::where('email', 'admin@clinica.test')->first();
        auth()->setUser($caja);

        $cuentas = PatientAccount::with('patient')->where('estado', 'abierta')->get();
        foreach ($cuentas->values() as $i => $cuenta) {
            if ($i % 5 === 4) {
                continue; // algunas cuentas quedan abiertas
            }
            $factura = $facturacion->emitir($cuenta, $caja);
            $emision = today()->subDays([3, 12, 25, 40, 55, 70, 95, 8][$i % 8]);
            $factura->update(['fecha_emision' => $emision, 'fecha_vencimiento' => $emision->copy()->addDays(30), 'created_at' => $emision]);

            $modo = $i % 6;
            if ($modo <= 2) {
                $p = $facturacion->registrarPago($factura, $factura->total, ['efectivo', 'yape', 'tarjeta'][$modo], null, $caja);
                $p->update(['fecha' => $emision->copy()->setTime(12, 0)]);
            } elseif ($modo === 3) {
                $p = $facturacion->registrarPago($factura, round($factura->total / 2, 2), 'efectivo', null, $caja);
                $p->update(['fecha' => $emision->copy()->setTime(12, 0)]);
            } elseif ($modo === 5 && $i === 5) {
                auth()->setUser($admin);
                $facturacion->anular($factura, 'Error en los datos del cliente', $admin);
                auth()->setUser($caja);
            }
        }

        // Recordatorios de citas próximas y alertas de vacunas vencidas
        \Illuminate\Support\Facades\Artisan::call('clinica:recordatorios', ['--vacunas' => true]);

        // Aperturas de notificaciones (aprox. 60 % correos y 40 % SMS)
        foreach (NotificationLog::where('estado', 'enviado')->get() as $n) {
            if (mt_rand(1, 100) <= ($n->canal === 'email' ? 60 : 40)) {
                $n->update(['estado' => 'abierto', 'abierto_at' => $n->created_at->copy()->addMinutes(mt_rand(5, 600))]);
            }
        }

        // Accesos de tutores a documentos en el portal
        foreach (Prescription::with('patient.tutors')->get() as $i => $r) {
            if ($i % 3 !== 0 && $t = $r->patient->tutors->first()) {
                DocumentAccess::create(['tutor_id' => $t->id, 'patient_id' => $r->patient_id, 'documento_tipo' => 'receta', 'documento_id' => $r->id, 'accion' => 'descarga', 'ip' => '127.0.0.1']);
            }
        }
        foreach (LabOrder::with('patient.tutors')->where('estado', 'completada')->get() as $i => $o) {
            if ($i % 2 === 0 && $t = $o->patient->tutors->first()) {
                DocumentAccess::create(['tutor_id' => $t->id, 'patient_id' => $o->patient_id, 'documento_tipo' => 'resultado', 'documento_id' => $o->id, 'accion' => 'visualizacion', 'ip' => '127.0.0.1']);
            }
        }

        auth()->logout();
    }
}
