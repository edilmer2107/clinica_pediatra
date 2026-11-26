<?php

namespace App\Services\Interoperabilidad;

use App\Exceptions\ReglaNegocioException;
use App\Models\AnthropometricMeasurement;
use App\Models\Patient;
use App\Models\Tutor;
use App\Services\Pacientes\PercentilOmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Exportación e importación de información clínica en HL7 FHIR R4 (JSON).
 * Terminologías: LOINC (mediciones y percentiles), CVX (vacunas), CIE-10 (diagnósticos).
 */
class FhirService
{
    public const LOINC = [
        'peso_kg' => ['29463-7', 'Body weight', 'kg'],
        'talla_cm' => ['8302-2', 'Body height', 'cm'],
        'perimetro_cefalico_cm' => ['9843-4', 'Head Occipital-frontal circumference', 'cm'],
        'imc' => ['39156-5', 'Body mass index (BMI) [Ratio]', 'kg/m2'],
        'p_peso_edad' => ['8336-0', 'Body weight [Percentile] Per age', '%'],
        'p_talla_edad' => ['8303-0', 'Body height [Percentile] Per age', '%'],
        'p_pc_edad' => ['8289-1', 'Head Occipital-frontal circumference [Percentile] Per age and sex', '%'],
        'p_imc_edad' => ['59576-9', 'Body mass index (BMI) [Percentile] Per age and sex', '%'],
    ];

    private const IDENTIFICADOR_HC = 'urn:clinica-pediatrica:historia-clinica';

    private const IDENTIFICADOR_DNI = 'urn:oid:2.16.604.1.1.1.1'; // DNI Perú (referencial)

    public function exportarPaciente(Patient $paciente): array
    {
        $paciente->load(['tutors', 'measurements', 'immunizations.vaccine', 'immunizations.lot', 'consultations.diagnoses.cie10', 'prescriptions.items.medication']);
        $pid = 'Patient/'.$paciente->id;
        $entradas = [$this->entrada($this->pacienteFhir($paciente))];

        foreach ($paciente->tutors as $t) {
            $entradas[] = $this->entrada([
                'resourceType' => 'RelatedPerson', 'id' => 'tutor-'.$t->id, 'patient' => ['reference' => $pid],
                'identifier' => [['system' => self::IDENTIFICADOR_DNI, 'value' => $t->dni]],
                'relationship' => [['text' => $t->pivot->parentesco]],
                'name' => [['family' => $t->apellidos, 'given' => [$t->nombres]]],
                'telecom' => array_values(array_filter([
                    ['system' => 'phone', 'value' => $t->telefono, 'use' => 'mobile'],
                    $t->email ? ['system' => 'email', 'value' => $t->email] : null,
                ])),
            ]);
        }

        foreach ($paciente->measurements as $m) {
            foreach (self::LOINC as $campo => [$codigo, $display, $unidad]) {
                if ($m->{$campo} === null) {
                    continue;
                }
                $obs = [
                    'resourceType' => 'Observation', 'id' => "obs-{$m->id}-{$codigo}", 'status' => 'final',
                    'category' => [['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/observation-category', 'code' => 'vital-signs']]]],
                    'code' => ['coding' => [['system' => 'http://loinc.org', 'code' => $codigo, 'display' => $display]]],
                    'subject' => ['reference' => $pid], 'effectiveDateTime' => $m->fecha->toDateString(),
                    'valueQuantity' => ['value' => (float) $m->{$campo}, 'unit' => $unidad, 'system' => 'http://unitsofmeasure.org', 'code' => $unidad],
                ];
                $zCampo = str_starts_with($campo, 'p_') ? 'z_'.substr($campo, 2) : null;
                if ($zCampo && $m->{$zCampo} !== null) {
                    $obs['note'] = [['text' => "Z-score OMS: {$m->{$zCampo}}"]];
                }
                $entradas[] = $this->entrada($obs);
            }
        }

        foreach ($paciente->immunizations as $i) {
            $entradas[] = $this->entrada([
                'resourceType' => 'Immunization', 'id' => 'imm-'.$i->id, 'status' => 'completed',
                'vaccineCode' => ['coding' => [['system' => 'http://hl7.org/fhir/sid/cvx', 'code' => $i->vaccine->codigo_cvx, 'display' => $i->vaccine->nombre]]],
                'patient' => ['reference' => $pid], 'occurrenceDateTime' => $i->fecha_aplicacion->toDateString(),
                'lotNumber' => $i->lot->numero_lote, 'expirationDate' => $i->lot->fecha_vencimiento->toDateString(),
                'site' => ['text' => $i->sitio_aplicacion], 'route' => ['text' => $i->vaccine->via],
            ]);
        }

        foreach ($paciente->consultations as $c) {
            foreach ($c->diagnoses as $d) {
                $entradas[] = $this->entrada([
                    'resourceType' => 'Condition', 'id' => 'cond-'.$d->id,
                    'code' => ['coding' => [['system' => 'http://hl7.org/fhir/sid/icd-10', 'code' => $d->cie10->codigo, 'display' => $d->cie10->descripcion]]],
                    'subject' => ['reference' => $pid], 'recordedDate' => $c->fecha->toDateString(),
                    'verificationStatus' => ['coding' => [['system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status', 'code' => $d->tipo === 'presuntivo' ? 'provisional' : 'confirmed']]],
                ]);
            }
        }

        foreach ($paciente->prescriptions as $r) {
            foreach ($r->items as $it) {
                $entradas[] = $this->entrada([
                    'resourceType' => 'MedicationRequest', 'id' => 'medreq-'.$it->id, 'status' => $r->estado === 'anulada' ? 'cancelled' : 'active', 'intent' => 'order',
                    'medicationCodeableConcept' => ['text' => $it->medication->nombre_completo],
                    'subject' => ['reference' => $pid], 'authoredOn' => $r->created_at->toDateString(), 'groupIdentifier' => ['value' => $r->codigo],
                    'dosageInstruction' => [['text' => "{$it->dosis_por_toma_mg} mg cada {$it->frecuencia_horas} h por {$it->duracion_dias} días ({$it->via})",
                        'timing' => ['repeat' => ['frequency' => 1, 'period' => $it->frecuencia_horas, 'periodUnit' => 'h']],
                        'doseAndRate' => [['doseQuantity' => ['value' => (float) $it->dosis_por_toma_mg, 'unit' => 'mg']]]]],
                ]);
            }
        }

        return [
            'resourceType' => 'Bundle', 'id' => (string) Str::uuid(), 'type' => 'collection',
            'timestamp' => now()->toIso8601String(), 'total' => count($entradas), 'entry' => $entradas,
        ];
    }

    private function pacienteFhir(Patient $p): array
    {
        return array_filter([
            'resourceType' => 'Patient', 'id' => (string) $p->id,
            'identifier' => array_values(array_filter([
                ['system' => self::IDENTIFICADOR_HC, 'value' => $p->historia_clinica],
                $p->dni ? ['system' => self::IDENTIFICADOR_DNI, 'value' => $p->dni] : null,
            ])),
            'name' => [['family' => $p->apellidos, 'given' => [$p->nombres]]],
            'gender' => $p->sexo === 'M' ? 'male' : 'female',
            'birthDate' => $p->fecha_nacimiento->toDateString(),
            'address' => $p->direccion ? [['text' => $p->direccion]] : null,
        ]);
    }

    private function entrada(array $recurso): array
    {
        return ['fullUrl' => 'urn:uuid:'.Str::uuid(), 'resource' => $recurso];
    }

    /**
     * Importa un Bundle FHIR: crea o identifica al paciente (por DNI) y registra
     * sus mediciones antropométricas (Observation LOINC peso/talla/PC).
     *
     * @return array{paciente: Patient, creado: bool, observaciones: int, omitidas: int}
     */
    public function importar(array $bundle, PercentilOmsService $oms): array
    {
        if (($bundle['resourceType'] ?? null) !== 'Bundle' || ! is_array($bundle['entry'] ?? null)) {
            throw new ReglaNegocioException('El archivo no es un Bundle FHIR válido.');
        }
        $recursos = collect($bundle['entry'])->pluck('resource')->filter(fn ($r) => is_array($r));
        $pacienteFhir = $recursos->firstWhere('resourceType', 'Patient');
        if (! $pacienteFhir) {
            throw new ReglaNegocioException('El Bundle no contiene un recurso Patient.');
        }

        $dni = collect($pacienteFhir['identifier'] ?? [])->firstWhere('system', self::IDENTIFICADOR_DNI)['value'] ?? null;
        $nombre = $pacienteFhir['name'][0] ?? [];
        $nacimiento = $pacienteFhir['birthDate'] ?? null;
        $sexo = ['male' => 'M', 'female' => 'F'][$pacienteFhir['gender'] ?? ''] ?? null;

        if (! $nacimiento || ! $sexo || empty($nombre['family']) || empty($nombre['given'][0])) {
            throw new ReglaNegocioException('El recurso Patient debe incluir nombre, sexo (male/female) y fecha de nacimiento.');
        }
        if ($dni !== null && ! preg_match('/^\d{8}$/', (string) $dni)) {
            throw new ReglaNegocioException('El DNI del paciente en el Bundle no es válido.');
        }
        $fechaNac = Carbon::parse($nacimiento);
        if ($fechaNac->isFuture() || $fechaNac->lt(now()->subYears(18))) {
            throw new ReglaNegocioException('La fecha de nacimiento no corresponde a un paciente pediátrico.');
        }

        return DB::transaction(function () use ($recursos, $dni, $nombre, $fechaNac, $sexo, $oms) {
            $existente = $dni ? Patient::where('dni', $dni)->first() : null;
            $paciente = $existente ?? Patient::create([
                'dni' => $dni, 'nombres' => mb_substr(strip_tags((string) $nombre['given'][0]), 0, 80),
                'apellidos' => mb_substr(strip_tags((string) $nombre['family']), 0, 80), 'fecha_nacimiento' => $fechaNac, 'sexo' => $sexo,
            ]);

            // Tutores (RelatedPerson)
            foreach ($recursos->where('resourceType', 'RelatedPerson') as $rp) {
                $dniTutor = $rp['identifier'][0]['value'] ?? null;
                $tel = collect($rp['telecom'] ?? [])->firstWhere('system', 'phone')['value'] ?? null;
                if (! $dniTutor || ! preg_match('/^\d{8}$/', $dniTutor) || ! $tel || ! preg_match('/^9\d{8}$/', $tel)) {
                    continue;
                }
                $tutor = Tutor::firstOrCreate(['dni' => $dniTutor], [
                    'nombres' => mb_substr(strip_tags((string) ($rp['name'][0]['given'][0] ?? 'Sin nombre')), 0, 80),
                    'apellidos' => mb_substr(strip_tags((string) ($rp['name'][0]['family'] ?? '')), 0, 80),
                    'telefono' => $tel, 'email' => collect($rp['telecom'] ?? [])->firstWhere('system', 'email')['value'] ?? null,
                ]);
                if (! $paciente->tutors()->whereKey($tutor->id)->exists()) {
                    $paciente->tutors()->attach($tutor->id, ['parentesco' => mb_substr((string) ($rp['relationship'][0]['text'] ?? 'Otro'), 0, 30), 'es_principal' => ! $paciente->tutors()->exists()]);
                }
            }

            // Observaciones antropométricas agrupadas por fecha
            $porFecha = [];
            $omitidas = 0;
            $mapa = ['29463-7' => 'peso_kg', '8302-2' => 'talla_cm', '9843-4' => 'perimetro_cefalico_cm'];
            foreach ($recursos->where('resourceType', 'Observation') as $o) {
                $codigo = $o['code']['coding'][0]['code'] ?? null;
                $fecha = $o['effectiveDateTime'] ?? null;
                $valor = $o['valueQuantity']['value'] ?? null;
                if (! isset($mapa[$codigo])) {
                    continue;
                }
                if (! $fecha || ! is_numeric($valor) || $valor <= 0) {
                    $omitidas++;

                    continue;
                }
                $porFecha[substr($fecha, 0, 10)][$mapa[$codigo]] = (float) $valor;
            }

            $importadas = 0;
            ksort($porFecha);
            foreach ($porFecha as $fecha => $v) {
                if (! isset($v['peso_kg'], $v['talla_cm'])) {
                    $omitidas++;

                    continue;
                }
                $f = Carbon::parse($fecha);
                if ($f->lt($paciente->fecha_nacimiento) || $f->isFuture()
                    || $paciente->measurements()->whereDate('fecha', $f)->exists()) {
                    $omitidas++;

                    continue;
                }
                $previas = $paciente->measurements()->whereDate('fecha', '<', $f)->get();
                $e = $oms->evaluar($paciente, $f, $v['peso_kg'], $v['talla_cm'], $v['perimetro_cefalico_cm'] ?? null, $previas);
                AnthropometricMeasurement::create([
                    'patient_id' => $paciente->id, 'fecha' => $f, 'edad_dias' => $e['edad_dias'], 'peso_kg' => $v['peso_kg'], 'talla_cm' => $v['talla_cm'],
                    'perimetro_cefalico_cm' => $v['perimetro_cefalico_cm'] ?? null, 'imc' => $e['imc'],
                    'z_peso_edad' => $e['z_peso_edad'] ?? null, 'p_peso_edad' => $e['p_peso_edad'] ?? null,
                    'z_talla_edad' => $e['z_talla_edad'] ?? null, 'p_talla_edad' => $e['p_talla_edad'] ?? null,
                    'z_pc_edad' => $e['z_pc_edad'] ?? null, 'p_pc_edad' => $e['p_pc_edad'] ?? null,
                    'z_imc_edad' => $e['z_imc_edad'] ?? null, 'p_imc_edad' => $e['p_imc_edad'] ?? null,
                    'alertas' => $e['alertas'], 'tiene_alerta' => collect($e['alertas'])->where('tipo', '!=', 'info')->isNotEmpty(),
                ]);
                $importadas++;
            }

            return ['paciente' => $paciente, 'creado' => ! $existente, 'observaciones' => $importadas, 'omitidas' => $omitidas];
        });
    }
}
