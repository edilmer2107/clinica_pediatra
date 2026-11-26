<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Ficha clínica pediátrica: antecedentes prenatales, natales y posnatales (campos obligatorios).
 */
class FichaClinicaController extends Controller
{
    public function edit(Patient $paciente): View
    {
        return view('pacientes.ficha', ['paciente' => $paciente, 'ficha' => $paciente->pediatricRecord]);
    }

    public function update(Request $request, Patient $paciente, AuditService $audit): RedirectResponse
    {
        $datos = $request->validate([
            'numero_controles_prenatales' => ['required', 'integer', 'min:0', 'max:20'],
            'edad_materna' => ['required', 'integer', 'min:10', 'max:60'],
            'patologias_embarazo' => ['required', 'string', 'max:1000'],
            'suplemento_hierro_acido_folico' => ['required', 'boolean'],
            'edad_gestacional_semanas' => ['required', 'integer', 'min:22', 'max:44'],
            'tipo_parto' => ['required', Rule::in(['eutocico', 'cesarea', 'instrumentado'])],
            'peso_nacer_kg' => ['required', 'numeric', 'min:0.4', 'max:6.5'],
            'talla_nacer_cm' => ['required', 'numeric', 'min:25', 'max:65'],
            'perimetro_cefalico_nacer_cm' => ['nullable', 'numeric', 'min:20', 'max:45'],
            'apgar_1' => ['required', 'integer', 'min:0', 'max:10'],
            'apgar_5' => ['required', 'integer', 'min:0', 'max:10'],
            'complicaciones_parto' => ['required', 'string', 'max:1000'],
            'lactancia' => ['required', Rule::in(['exclusiva', 'mixta', 'formula'])],
            'tamizaje_neonatal' => ['required', 'boolean'],
            'hospitalizaciones' => ['required', 'string', 'max:1000'],
            'enfermedades_previas' => ['required', 'string', 'max:1000'],
            'antecedentes_familiares' => ['required', 'string', 'max:1000'],
        ], [
            'edad_gestacional_semanas.min' => 'La edad gestacional debe estar entre 22 y 44 semanas.',
            'edad_gestacional_semanas.max' => 'La edad gestacional debe estar entre 22 y 44 semanas.',
            'peso_nacer_kg.max' => 'El peso al nacer no puede superar 6.5 kg.',
        ]);

        $antes = $paciente->pediatricRecord?->toArray();
        $ficha = $paciente->pediatricRecord()->updateOrCreate([], $datos + ['registrado_por' => $request->user()->id]);
        $audit->registrar($antes ? 'editar_ficha' : 'crear_ficha', 'pacientes', "Ficha clínica pediátrica de {$paciente->historia_clinica}.", $ficha, $antes, $ficha->getChanges() ?: $datos);

        return redirect()->route('pacientes.show', [$paciente, 'tab' => 'ficha'])->with('success', 'Ficha clínica guardada.');
    }
}
