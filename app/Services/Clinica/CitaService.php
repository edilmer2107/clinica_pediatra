<?php

namespace App\Services\Clinica;

use App\Exceptions\ReglaNegocioException;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Agenda de citas presenciales y por telemedicina.
 * Política: cancelación o reprogramación solo hasta 24 horas antes de la cita.
 */
class CitaService
{
    public const HORA_INICIO = 8;

    public const HORA_FIN = 19;

    public function __construct(private readonly AuditService $audit) {}

    public function agendar(array $datos): Appointment
    {
        $paciente = Patient::findOrFail($datos['patient_id']);
        $medico = User::findOrFail($datos['doctor_id']);
        $inicio = Carbon::parse($datos['fecha_hora']);
        $duracion = (int) ($datos['duracion_minutos'] ?? 30);

        $this->validarHorario($medico, $paciente, $inicio, $duracion);

        if (! $paciente->consentimientoVigente()) {
            throw new ReglaNegocioException('El paciente no tiene consentimiento informado vigente; regístrelo antes de agendar.');
        }

        $modalidad = $datos['modalidad'];
        $cita = Appointment::create([
            'patient_id' => $paciente->id,
            'doctor_id' => $medico->id,
            'tutor_id' => $datos['tutor_id'] ?? $paciente->tutorPrincipal()?->id,
            'fecha_hora' => $inicio,
            'duracion_minutos' => $duracion,
            'modalidad' => $modalidad,
            'servicio' => $modalidad === 'telemedicina' ? 'telemedicina' : $datos['servicio'],
            'estado' => 'programada',
            'motivo' => $datos['motivo'] ?? null,
            'enlace_telemedicina' => $modalidad === 'telemedicina' ? $this->generarEnlace() : null,
            'reprogramada_desde_id' => $datos['reprogramada_desde_id'] ?? null,
            'creado_por' => Auth::id(),
        ]);

        return $cita;
    }

    public function validarHorario(User $medico, Patient $paciente, Carbon $inicio, int $duracion, ?int $ignorarId = null): void
    {
        if (! $medico->hasRole('medico')) {
            throw new ReglaNegocioException('El profesional seleccionado no es médico.');
        }
        if ($inicio->lte(now())) {
            throw new ReglaNegocioException('La cita debe programarse en una fecha y hora futura.');
        }
        if ($inicio->isSunday()) {
            throw new ReglaNegocioException('La clínica no atiende los domingos.');
        }
        $fin = $inicio->copy()->addMinutes($duracion);
        if ($inicio->hour < self::HORA_INICIO || $fin->gt($inicio->copy()->setTime(self::HORA_FIN, 0))) {
            throw new ReglaNegocioException('El horario de atención es de 08:00 a 19:00.');
        }

        $cruce = fn ($q) => $q->whereIn('estado', ['programada', 'confirmada'])
            ->when($ignorarId, fn ($w) => $w->where('id', '!=', $ignorarId))
            ->where('fecha_hora', '<', $fin)
            ->whereRaw("fecha_hora + (duracion_minutos || ' minutes')::interval > ?", [$inicio]);

        if (Appointment::where('doctor_id', $medico->id)->where($cruce)->exists()) {
            throw new ReglaNegocioException("{$medico->name} ya tiene una cita en ese horario.");
        }
        if (Appointment::where('patient_id', $paciente->id)->where($cruce)->exists()) {
            throw new ReglaNegocioException('El paciente ya tiene una cita en ese horario.');
        }
    }

    /** ¿Se puede cancelar/reprogramar según la política de 24 horas? */
    public static function dentroDePolitica(Appointment $cita, ?Carbon $ahora = null): bool
    {
        $ahora ??= now();

        return $ahora->diffInMinutes($cita->fecha_hora, false) >= config('clinica.horas_minimas_cancelacion') * 60;
    }

    public function cancelar(Appointment $cita, string $motivo): Appointment
    {
        $this->verificarModificable($cita);
        $cita->update(['estado' => 'cancelada', 'motivo_cancelacion' => $motivo, 'cancelada_at' => now()]);
        $this->audit->registrar('cancelar_cita', 'clinica', "Cancelación de la cita #{$cita->id} ({$cita->fecha_hora->format('d/m/Y H:i')}). Motivo: {$motivo}", $cita);

        return $cita;
    }

    public function reprogramar(Appointment $cita, Carbon $nuevaFecha, string $motivo): Appointment
    {
        $this->verificarModificable($cita);

        return DB::transaction(function () use ($cita, $nuevaFecha, $motivo) {
            $cita->update(['estado' => 'reprogramada', 'motivo_cancelacion' => $motivo, 'cancelada_at' => now()]);
            $nueva = $this->agendar([
                'patient_id' => $cita->patient_id, 'doctor_id' => $cita->doctor_id, 'tutor_id' => $cita->tutor_id,
                'fecha_hora' => $nuevaFecha, 'duracion_minutos' => $cita->duracion_minutos, 'modalidad' => $cita->modalidad,
                'servicio' => $cita->servicio, 'motivo' => $cita->motivo, 'reprogramada_desde_id' => $cita->id,
            ]);
            $this->audit->registrar('reprogramar_cita', 'clinica', "Cita #{$cita->id} reprogramada a {$nuevaFecha->format('d/m/Y H:i')} (nueva #{$nueva->id}). Motivo: {$motivo}", $nueva);

            return $nueva;
        });
    }

    public function marcarNoAsistio(Appointment $cita): void
    {
        if (! $cita->esModificable()) {
            throw new ReglaNegocioException('Solo una cita programada o confirmada puede marcarse como no asistida.');
        }
        if ($cita->fecha_hora->isFuture()) {
            throw new ReglaNegocioException('No se puede marcar inasistencia antes de la hora de la cita.');
        }
        $cita->update(['estado' => 'no_asistio']);
    }

    private function verificarModificable(Appointment $cita): void
    {
        if (! $cita->esModificable()) {
            throw new ReglaNegocioException('La cita ya no puede modificarse (estado: '.Appointment::ESTADOS[$cita->estado].').');
        }
        if (! self::dentroDePolitica($cita)) {
            throw new ReglaNegocioException('Según la política de la clínica, las citas solo pueden cancelarse o reprogramarse hasta '.config('clinica.horas_minimas_cancelacion').' horas antes.');
        }
    }

    private function generarEnlace(): string
    {
        return rtrim(config('clinica.telemedicina_url'), '/').'/ClinicaPediatrica-'.Str::lower(Str::random(16));
    }
}
