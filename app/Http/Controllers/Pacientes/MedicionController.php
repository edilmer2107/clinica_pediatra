<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Models\AnthropometricMeasurement;
use App\Models\Patient;
use App\Services\Pacientes\PercentilOmsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MedicionController extends Controller
{
    public function store(Request $request, Patient $paciente, PercentilOmsService $oms): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$paciente->fecha_nacimiento->toDateString()],
            'peso_kg' => ['required', 'numeric', 'min:0.4', 'max:150'],
            'talla_cm' => ['required', 'numeric', 'min:25', 'max:220'],
            'perimetro_cefalico_cm' => ['nullable', 'numeric', 'min:20', 'max:65'],
            'consultation_id' => ['nullable', 'integer', 'exists:consultations,id'],
        ], [
            'fecha.after_or_equal' => 'La fecha de la medición no puede ser anterior al nacimiento.',
        ]);

        $fecha = Carbon::parse($datos['fecha']);
        $previas = $paciente->measurements()->whereDate('fecha', '<', $fecha)
            ->whereDate('fecha', '>=', $fecha->copy()->subDays(PercentilOmsService::DIAS_VENTANA_CRUCE))->get();

        $evaluacion = $oms->evaluar($paciente, $fecha, (float) $datos['peso_kg'], (float) $datos['talla_cm'],
            isset($datos['perimetro_cefalico_cm']) ? (float) $datos['perimetro_cefalico_cm'] : null, $previas);

        $alertasRelevantes = array_values(array_filter($evaluacion['alertas'], fn ($a) => $a['tipo'] !== 'info'));

        AnthropometricMeasurement::create($datos + [
            'patient_id' => $paciente->id,
            'edad_dias' => $evaluacion['edad_dias'],
            'imc' => $evaluacion['imc'],
            'z_peso_edad' => $evaluacion['z_peso_edad'] ?? null, 'p_peso_edad' => $evaluacion['p_peso_edad'] ?? null,
            'z_talla_edad' => $evaluacion['z_talla_edad'] ?? null, 'p_talla_edad' => $evaluacion['p_talla_edad'] ?? null,
            'z_pc_edad' => $evaluacion['z_pc_edad'] ?? null, 'p_pc_edad' => $evaluacion['p_pc_edad'] ?? null,
            'z_imc_edad' => $evaluacion['z_imc_edad'] ?? null, 'p_imc_edad' => $evaluacion['p_imc_edad'] ?? null,
            'alertas' => $evaluacion['alertas'],
            'tiene_alerta' => $alertasRelevantes !== [],
            'registrado_por' => $request->user()->id,
        ]);

        $redireccion = $request->input('volver') === 'consulta' && ! empty($datos['consultation_id'])
            ? redirect()->route('consultas.show', $datos['consultation_id'])
            : redirect()->route('pacientes.show', [$paciente, 'tab' => 'crecimiento']);

        return $alertasRelevantes
            ? $redireccion->with('warning', 'Medición registrada con ALERTAS: '.implode(' · ', array_column($alertasRelevantes, 'mensaje')))
            : $redireccion->with('success', 'Medición registrada. Valores dentro de rangos esperados.');
    }
}
