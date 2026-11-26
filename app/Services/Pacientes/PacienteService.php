<?php

namespace App\Services\Pacientes;

use App\Exceptions\ReglaNegocioException;
use App\Models\Consent;
use App\Models\Patient;
use App\Models\Tutor;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class PacienteService
{
    public const VERSION_CONSENTIMIENTO = '1.0';

    public function __construct(private readonly AuditService $audit) {}

    /**
     * Registra paciente + tutor principal (nuevo o existente por DNI) + consentimiento.
     */
    public function registrar(array $paciente, array $tutor, array $vinculo, array $consentimiento): Patient
    {
        if (empty($consentimiento['acepta_tratamiento_datos'])) {
            throw new ReglaNegocioException('Se requiere el consentimiento informado del tutor para el tratamiento de datos del menor (Ley N° 29733).');
        }

        return DB::transaction(function () use ($paciente, $tutor, $vinculo, $consentimiento) {
            $nuevo = Patient::create($paciente + ['historia_clinica' => Patient::siguienteHistoriaClinica()]);

            $tutorModelo = Tutor::firstOrCreate(['dni' => $tutor['dni']], $tutor);
            $this->vincular($nuevo, $tutorModelo, $vinculo['parentesco'], true, $vinculo['puede_retirar_medicamentos'] ?? true);
            $this->registrarConsentimiento($nuevo, $tutorModelo, $consentimiento);

            $this->audit->registrar('registrar_paciente', 'pacientes', "Registro del paciente {$nuevo->historia_clinica} con tutor DNI {$tutorModelo->dni}.", $nuevo);

            return $nuevo;
        });
    }

    public function vincular(Patient $paciente, Tutor $tutor, string $parentesco, bool $principal, bool $puedeRetirar = true): void
    {
        if ($paciente->tutors()->whereKey($tutor->id)->exists()) {
            throw new ReglaNegocioException('El tutor ya está vinculado a este paciente.');
        }

        DB::transaction(function () use ($paciente, $tutor, $parentesco, $principal, $puedeRetirar) {
            if ($principal) {
                DB::table('patient_tutor')->where('patient_id', $paciente->id)->update(['es_principal' => false]);
            }
            $paciente->tutors()->attach($tutor->id, [
                'parentesco' => $parentesco,
                'es_principal' => $principal,
                'puede_retirar_medicamentos' => $puedeRetirar,
            ]);
        });
    }

    public function desvincular(Patient $paciente, Tutor $tutor): void
    {
        if ($paciente->tutors()->count() <= 1) {
            throw new ReglaNegocioException('El paciente debe tener al menos un tutor vinculado.');
        }
        $paciente->tutors()->detach($tutor->id);
        if (! $paciente->tutors()->wherePivot('es_principal', true)->exists()) {
            $primero = $paciente->tutors()->first();
            $paciente->tutors()->updateExistingPivot($primero->id, ['es_principal' => true]);
        }
        $this->audit->registrar('desvincular_tutor', 'pacientes', "Se desvinculó al tutor DNI {$tutor->dni} del paciente {$paciente->historia_clinica}.", $paciente);
    }

    public function registrarConsentimiento(Patient $paciente, Tutor $tutor, array $datos): Consent
    {
        if (! $paciente->tutors()->whereKey($tutor->id)->exists()) {
            throw new ReglaNegocioException('Solo un tutor vinculado puede otorgar el consentimiento.');
        }

        $consentimiento = Consent::create([
            'patient_id' => $paciente->id,
            'tutor_id' => $tutor->id,
            'version' => self::VERSION_CONSENTIMIENTO,
            'acepta_tratamiento_datos' => (bool) ($datos['acepta_tratamiento_datos'] ?? false),
            'acepta_atencion_medica' => (bool) ($datos['acepta_atencion_medica'] ?? false),
            'acepta_notificaciones' => (bool) ($datos['acepta_notificaciones'] ?? false),
            'aceptado_at' => now(),
            'ip' => Request::ip(),
            'registrado_por' => Auth::id(),
        ]);

        $this->audit->registrar('consentimiento', 'pacientes', "Consentimiento v{$consentimiento->version} del tutor DNI {$tutor->dni} para {$paciente->historia_clinica}.", $consentimiento);

        return $consentimiento;
    }

    public function revocarConsentimiento(Consent $consentimiento): void
    {
        $consentimiento->update(['revocado_at' => now()]);
        $this->audit->registrar('revocar_consentimiento', 'pacientes', "Revocación del consentimiento #{$consentimiento->id}.", $consentimiento);
    }
}
