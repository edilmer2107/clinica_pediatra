<?php

namespace App\Services\Portal;

use App\Mail\NotificacionTutor;
use App\Models\Appointment;
use App\Models\LabOrder;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Tutor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Notificaciones proactivas a tutores por correo y SMS, con registro de envío
 * y de apertura (pixel en correos y enlace corto en SMS).
 */
class NotificacionService
{
    public function __construct(private readonly SmsGateway $sms) {}

    public function recordatorioCita(Appointment $cita): int
    {
        $cita->loadMissing(['patient.tutors', 'doctor']);
        $modalidad = $cita->modalidad === 'telemedicina'
            ? "por telemedicina. Enlace: {$cita->enlace_telemedicina}"
            : 'presencial en la clínica';
        $texto = "Recordatorio: {$cita->patient->nombres} tiene cita el {$cita->fecha_hora->format('d/m/Y')} a las {$cita->fecha_hora->format('H:i')} con {$cita->doctor->name}, {$modalidad}. Puede cancelar o reprogramar hasta 24 h antes.";

        $enviadas = $this->notificarTutores($cita->patient, 'recordatorio_cita', 'Recordatorio de cita', $texto, $cita);
        $cita->update(['recordatorio_enviado_at' => now()]);

        return $enviadas;
    }

    public function vacunasVencidas(Patient $paciente, array $vacunas): int
    {
        $lista = implode(', ', $vacunas);
        $texto = "Estimado tutor: {$paciente->nombres} tiene vacunas pendientes o vencidas ({$lista}). Acérquese a la clínica para ponerlas al día.";

        return $this->notificarTutores($paciente, 'vacuna_vencida', 'Vacunas pendientes', $texto, $paciente);
    }

    public function recetaLista(Prescription $receta): int
    {
        $receta->loadMissing('patient.tutors');
        $texto = "La receta {$receta->codigo} de {$receta->patient->nombres} está lista para retiro en la farmacia de la clínica. Traiga su DNI.";

        return $this->notificarTutores($receta->patient, 'receta_lista', 'Receta lista para retiro', $texto, $receta);
    }

    public function resultadoListo(LabOrder $orden): int
    {
        $orden->loadMissing('patient.tutors');
        $texto = "Los resultados de la orden {$orden->codigo} de {$orden->patient->nombres} ya están disponibles en el portal de tutores.";

        return $this->notificarTutores($orden->patient, 'resultado_listo', 'Resultados disponibles', $texto, $orden);
    }

    /** Envía por correo y SMS a los tutores con consentimiento de notificaciones. */
    public function notificarTutores(Patient $paciente, string $tipo, string $asunto, string $texto, ?Model $referencia = null): int
    {
        $paciente->loadMissing('tutors');
        $enviadas = 0;

        foreach ($paciente->tutors as $tutor) {
            if (! $this->aceptaNotificaciones($paciente, $tutor)) {
                continue;
            }
            if ($tutor->email) {
                $enviadas += $this->enviarCorreo($tutor, $paciente, $tipo, $asunto, $texto, $referencia) ? 1 : 0;
            }
            if ($tutor->telefono) {
                $enviadas += $this->enviarSms($tutor, $paciente, $tipo, $texto, $referencia) ? 1 : 0;
            }
        }

        return $enviadas;
    }

    public function enviarCorreo(Tutor $tutor, ?Patient $paciente, string $tipo, string $asunto, string $texto, ?Model $referencia = null): bool
    {
        $log = $this->crearLog('email', $tipo, $tutor, $paciente, $tutor->email, $asunto, $texto, $referencia);

        try {
            Mail::to($tutor->email)->send(new NotificacionTutor(
                $asunto, $texto,
                route('notificaciones.pixel', $log->token_seguimiento),
                route('notificaciones.abrir', $log->token_seguimiento),
            ));
            $log->update(['estado' => 'enviado', 'proveedor' => config('mail.default'), 'enviado_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            $log->update(['estado' => 'fallido', 'error' => mb_substr($e->getMessage(), 0, 500)]);

            return false;
        }
    }

    public function enviarSms(Tutor $tutor, ?Patient $paciente, string $tipo, string $texto, ?Model $referencia = null): bool
    {
        $log = $this->crearLog('sms', $tipo, $tutor, $paciente, $tutor->telefono, null, $texto, $referencia);
        $mensaje = $texto.' Ver: '.route('notificaciones.abrir', $log->token_seguimiento);

        $resultado = $this->sms->enviar($tutor->telefono, $mensaje);
        $log->update([
            'mensaje' => $mensaje,
            'estado' => $resultado['ok'] ? 'enviado' : 'fallido',
            'proveedor' => $resultado['proveedor'],
            'proveedor_id' => $resultado['id'],
            'error' => $resultado['error'],
            'enviado_at' => $resultado['ok'] ? now() : null,
        ]);

        return $resultado['ok'];
    }

    public function registrarApertura(string $token): ?NotificationLog
    {
        $log = NotificationLog::where('token_seguimiento', $token)->first();
        if ($log && ! $log->abierto_at) {
            $log->update(['estado' => 'abierto', 'abierto_at' => now()]);
        }

        return $log;
    }

    private function aceptaNotificaciones(Patient $paciente, Tutor $tutor): bool
    {
        return $paciente->consents()
            ->where('tutor_id', $tutor->id)
            ->where('acepta_notificaciones', true)
            ->whereNull('revocado_at')
            ->exists();
    }

    private function crearLog(string $canal, string $tipo, Tutor $tutor, ?Patient $paciente, string $destinatario, ?string $asunto, string $texto, ?Model $referencia): NotificationLog
    {
        return NotificationLog::create([
            'canal' => $canal,
            'tipo' => $tipo,
            'tutor_id' => $tutor->id,
            'patient_id' => $paciente?->id,
            'destinatario' => $destinatario,
            'asunto' => $asunto,
            'mensaje' => $texto,
            'estado' => 'pendiente',
            'token_seguimiento' => Str::random(40),
            'referencia_type' => $referencia?->getMorphClass(),
            'referencia_id' => $referencia?->getKey(),
        ]);
    }
}
