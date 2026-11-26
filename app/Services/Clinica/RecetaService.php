<?php

namespace App\Services\Clinica;

use App\Exceptions\ReglaNegocioException;
use App\Models\Consultation;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

/**
 * Receta electrónica con validación de dosis pediátrica e interacciones.
 * - Si una dosis está fuera de rango, el médico debe justificarla para emitir.
 * - Si hay una interacción GRAVE, la receta se bloquea salvo justificación explícita.
 */
class RecetaService
{
    public function __construct(
        private readonly DosisPediatricaService $dosis,
        private readonly AuditService $audit,
    ) {}

    /**
     * Evalúa las líneas sin guardar (para mostrar alertas antes de emitir).
     *
     * @return array{lineas: array<int, array>, interacciones: array<int, array>}
     */
    public function evaluar(Patient $paciente, float $peso, ?float $talla, array $lineas): array
    {
        $resultado = [];
        $principios = [];

        foreach ($lineas as $i => $l) {
            $med = Medication::findOrFail($l['medication_id']);
            if ($med->es_vacuna) {
                throw new ReglaNegocioException('Las vacunas se registran en el módulo de vacunación, no en recetas.');
            }
            if ($paciente->alergias && str_contains(mb_strtolower($paciente->alergias), mb_strtolower($med->principio_activo))) {
                throw new ReglaNegocioException("El paciente es alérgico a {$med->principio_activo}.");
            }
            $calculo = $this->dosis->calcular($med, $l['metodo_calculo'], (float) $l['dosis_indicada'], $peso, $talla, (int) $l['frecuencia_horas'], (int) $l['duracion_dias']);
            $resultado[$i] = ['medicamento' => $med, 'calculo' => $calculo, 'datos' => $l];
            $principios[] = $med->principio_activo;
        }

        // Interacciones con la propia receta y con recetas activas del paciente (últimos 30 días)
        $activos = PrescriptionItem::query()
            ->whereHas('prescription', fn ($q) => $q->where('patient_id', $paciente->id)
                ->whereIn('estado', ['emitida', 'parcial', 'dispensada'])
                ->where('created_at', '>=', now()->subDays(30)))
            ->with('medication')->get()->pluck('medication.principio_activo')->all();

        return [
            'lineas' => $resultado,
            'interacciones' => $this->dosis->interacciones(array_merge($principios, $activos)),
        ];
    }

    public function emitir(Patient $paciente, User $medico, array $datos, ?Consultation $consulta = null): Prescription
    {
        $peso = (float) $datos['peso_kg'];
        $talla = isset($datos['talla_cm']) ? (float) $datos['talla_cm'] : null;
        $evaluacion = $this->evaluar($paciente, $peso, $talla, $datos['items']);

        $graves = array_filter($evaluacion['interacciones'], fn ($i) => $i['severidad'] === 'grave');
        $justificacionGeneral = trim((string) ($datos['justificacion_interaccion'] ?? ''));
        if ($graves && $justificacionGeneral === '') {
            $detalle = implode('; ', array_map(fn ($i) => "{$i['a']} + {$i['b']}: {$i['descripcion']}", $graves));
            throw new ReglaNegocioException("Interacción medicamentosa GRAVE detectada ({$detalle}). Modifique la receta o registre una justificación clínica.");
        }

        foreach ($evaluacion['lineas'] as $i => $linea) {
            if ($linea['calculo']['alerta_dosis'] && trim((string) ($linea['datos']['justificacion'] ?? '')) === '') {
                throw new ReglaNegocioException("{$linea['medicamento']->nombre}: ".implode(' ', $linea['calculo']['alertas']).' Debe justificar la dosis para continuar.');
            }
        }

        return DB::transaction(function () use ($paciente, $medico, $datos, $consulta, $peso, $talla, $evaluacion, $justificacionGeneral) {
            $receta = Prescription::create([
                'consultation_id' => $consulta?->id,
                'patient_id' => $paciente->id,
                'doctor_id' => $medico->id,
                'peso_kg' => $peso,
                'talla_cm' => $talla,
                'superficie_corporal' => $talla ? DosisPediatricaService::superficieCorporal($peso, $talla) : null,
                'estado' => 'emitida',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $principiosInteraccion = collect($evaluacion['interacciones'])->flatMap(fn ($i) => [$i['a'], $i['b']])->unique();

            foreach ($evaluacion['lineas'] as $linea) {
                $d = $linea['datos'];
                $c = $linea['calculo'];
                $med = $linea['medicamento'];
                $interaccion = $principiosInteraccion->contains(mb_strtolower($med->principio_activo));
                $alertas = $c['alertas'];
                if ($interaccion) {
                    $alertas[] = 'Interacción: '.collect($evaluacion['interacciones'])
                        ->filter(fn ($i) => in_array(mb_strtolower($med->principio_activo), [$i['a'], $i['b']], true))
                        ->map(fn ($i) => "{$i['a']} + {$i['b']} ({$i['severidad']})")->implode(', ');
                }

                $receta->items()->create([
                    'medication_id' => $med->id,
                    'metodo_calculo' => $d['metodo_calculo'],
                    'dosis_indicada' => $d['dosis_indicada'],
                    'dosis_diaria_mg' => $c['dosis_diaria_mg'],
                    'frecuencia_horas' => $d['frecuencia_horas'],
                    'dosis_por_toma_mg' => $c['dosis_por_toma_mg'],
                    'volumen_por_toma_ml' => $c['volumen_por_toma_ml'],
                    'duracion_dias' => $d['duracion_dias'],
                    'via' => $d['via'],
                    'cantidad' => $c['cantidad'],
                    'alerta_dosis' => $c['alerta_dosis'],
                    'alerta_interaccion' => $interaccion,
                    'alertas' => $alertas ? implode(' ', $alertas) : null,
                    'justificacion' => trim(($d['justificacion'] ?? '').' '.($interaccion ? $justificacionGeneral : '')) ?: null,
                    'indicaciones' => $d['indicaciones'] ?? null,
                ]);
            }

            $this->audit->registrar('emitir_receta', 'clinica', "Emisión de la receta {$receta->codigo} para {$paciente->historia_clinica}.", $receta, null,
                ['items' => $receta->items()->with('medication:id,nombre')->get(['medication_id', 'dosis_indicada', 'cantidad'])->toArray()]);

            return $receta->load('items.medication');
        });
    }

    /** Modificación de la posología de un ítem (solo si no se ha dispensado). Queda auditada. */
    public function modificarItem(PrescriptionItem $item, array $datos, string $motivo): PrescriptionItem
    {
        $receta = $item->prescription;
        if ($receta->estado !== 'emitida' || $item->cantidad_dispensada > 0) {
            throw new ReglaNegocioException('Solo se puede modificar una receta emitida que aún no ha sido dispensada.');
        }

        $calculo = $this->dosis->calcular($item->medication, $item->metodo_calculo, (float) $datos['dosis_indicada'], $receta->peso_kg, $receta->talla_cm, (int) $datos['frecuencia_horas'], (int) $datos['duracion_dias']);
        if ($calculo['alerta_dosis'] && trim((string) ($datos['justificacion'] ?? '')) === '') {
            throw new ReglaNegocioException(implode(' ', $calculo['alertas']).' Debe justificar la dosis.');
        }

        $antes = $item->only(['dosis_indicada', 'frecuencia_horas', 'duracion_dias', 'cantidad', 'dosis_diaria_mg']);
        $item->update([
            'dosis_indicada' => $datos['dosis_indicada'],
            'frecuencia_horas' => $datos['frecuencia_horas'],
            'duracion_dias' => $datos['duracion_dias'],
            'dosis_diaria_mg' => $calculo['dosis_diaria_mg'],
            'dosis_por_toma_mg' => $calculo['dosis_por_toma_mg'],
            'volumen_por_toma_ml' => $calculo['volumen_por_toma_ml'],
            'cantidad' => $calculo['cantidad'],
            'alerta_dosis' => $calculo['alerta_dosis'],
            'alertas' => $calculo['alertas'] ? implode(' ', $calculo['alertas']) : null,
            'justificacion' => $datos['justificacion'] ?? $item->justificacion,
        ]);

        $this->audit->registrar('modificar_receta', 'clinica', "Modificación de {$item->medication->nombre} en la receta {$receta->codigo}. Motivo: {$motivo}", $receta,
            $antes, $item->only(array_keys($antes)));

        return $item;
    }

    public function anular(Prescription $receta, string $motivo): void
    {
        if ($receta->estado !== 'emitida') {
            throw new ReglaNegocioException('Solo se puede anular una receta emitida sin dispensaciones.');
        }
        $receta->update(['estado' => 'anulada', 'observaciones' => trim(($receta->observaciones ?? '')."\nANULADA: {$motivo}")]);
        $this->audit->registrar('anular_receta', 'clinica', "Anulación de la receta {$receta->codigo}. Motivo: {$motivo}", $receta);
    }
}
