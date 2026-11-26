<?php

namespace App\Services\Clinica;

use App\Exceptions\ReglaNegocioException;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Facturacion\CuentaService;
use Illuminate\Support\Facades\DB;

class ConsultaService
{
    public const CODIGO_SERVICIO = [
        'general' => 'CON-GEN',
        'control_nino_sano' => 'CON-CRED',
        'telemedicina' => 'CON-TELE',
    ];

    public function __construct(
        private readonly CuentaService $cuentas,
        private readonly AuditService $audit,
    ) {}

    public function registrar(Patient $paciente, User $medico, array $datos, ?Appointment $cita = null): Consultation
    {
        if (! $paciente->consentimientoVigente()) {
            throw new ReglaNegocioException('No se puede registrar la atención: el paciente no tiene consentimiento informado vigente.');
        }
        if ($cita && ($cita->patient_id !== $paciente->id || ! $cita->esModificable())) {
            throw new ReglaNegocioException('La cita indicada no corresponde al paciente o ya fue atendida/cancelada.');
        }

        $plantilla = PlantillaHistoriaService::determinar($paciente);
        foreach (PlantillaHistoriaService::interrogatorio($plantilla) as $campo => [$etiqueta, $obligatorio]) {
            if ($obligatorio && trim((string) ($datos['interrogatorio'][$campo] ?? '')) === '') {
                throw new ReglaNegocioException("Complete el campo obligatorio del interrogatorio: {$etiqueta}.");
            }
        }

        return DB::transaction(function () use ($paciente, $medico, $datos, $cita, $plantilla) {
            $servicio = $cita?->servicio ?? $datos['servicio'];
            $consulta = Consultation::create([
                'appointment_id' => $cita?->id,
                'patient_id' => $paciente->id,
                'doctor_id' => $medico->id,
                'fecha' => now(),
                'plantilla' => $plantilla,
                'servicio' => $servicio,
                'motivo_consulta' => $datos['motivo_consulta'],
                'enfermedad_actual' => $datos['enfermedad_actual'] ?? null,
                'interrogatorio' => array_intersect_key($datos['interrogatorio'] ?? [], PlantillaHistoriaService::interrogatorio($plantilla)),
                'examen_fisico' => array_intersect_key($datos['examen_fisico'] ?? [], PlantillaHistoriaService::examenFisico($plantilla)),
                'temperatura' => $datos['temperatura'] ?? null,
                'frecuencia_cardiaca' => $datos['frecuencia_cardiaca'] ?? null,
                'frecuencia_respiratoria' => $datos['frecuencia_respiratoria'] ?? null,
                'saturacion_o2' => $datos['saturacion_o2'] ?? null,
                'presion_arterial' => $datos['presion_arterial'] ?? null,
                'plan_tratamiento' => $datos['plan_tratamiento'] ?? null,
                'indicaciones' => $datos['indicaciones'] ?? null,
                'estado' => 'abierta',
            ]);

            foreach ($datos['diagnosticos'] ?? [] as $i => $cie10Id) {
                if ($cie10Id) {
                    $consulta->diagnoses()->create(['cie10_code_id' => $cie10Id, 'tipo' => $datos['tipo_diagnostico'][$i] ?? 'definitivo', 'principal' => $i === 0]);
                }
            }

            $cita?->update(['estado' => 'atendida']);

            $servicioCatalogo = Service::where('codigo', self::CODIGO_SERVICIO[$servicio] ?? 'CON-GEN')->first();
            if ($servicioCatalogo) {
                $this->cuentas->agregarCargo($paciente, 'consulta', $servicioCatalogo->nombre.' - '.$medico->name, 1, $servicioCatalogo->precio, $consulta);
            }

            return $consulta;
        });
    }

    public function agregarProcedimiento(Consultation $consulta, Service $servicio, int $cantidad = 1): void
    {
        if ($servicio->tipo !== 'procedimiento') {
            throw new ReglaNegocioException('El servicio seleccionado no es un procedimiento.');
        }
        $this->cuentas->agregarCargo($consulta->patient, 'procedimiento', $servicio->nombre, $cantidad, $servicio->precio, $consulta);
    }

    public function cerrar(Consultation $consulta): void
    {
        if ($consulta->diagnoses()->count() === 0) {
            throw new ReglaNegocioException('Registre al menos un diagnóstico CIE-10 antes de cerrar la consulta.');
        }
        $consulta->update(['estado' => 'cerrada']);
        $this->audit->registrar('cerrar_consulta', 'clinica', "Cierre de la consulta #{$consulta->id} de {$consulta->patient->historia_clinica}.", $consulta);
    }
}
