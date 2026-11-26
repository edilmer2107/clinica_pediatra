<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\MilestoneAssessment;
use App\Models\Patient;
use App\Models\Psc35Screening;
use App\Services\Pacientes\DesarrolloService;
use App\Services\Pacientes\Psc35Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DesarrolloController extends Controller
{
    public function guardarHitos(Request $request, Patient $paciente): RedirectResponse
    {
        $datos = $request->validate([
            'hitos' => ['required', 'array'],
            'hitos.*' => ['nullable', 'in:1,0'],
        ]);

        $meses = $paciente->edadEnMeses();
        $registrados = 0;
        $alertas = 0;
        foreach ($datos['hitos'] as $hitoId => $logrado) {
            if ($logrado === null || $logrado === '') {
                continue;
            }
            $hito = Milestone::find($hitoId);
            if (! $hito) {
                continue;
            }
            $alerta = DesarrolloService::esAlerta((bool) $logrado, $meses, $hito->edad_limite_meses);
            MilestoneAssessment::create([
                'patient_id' => $paciente->id, 'milestone_id' => $hito->id, 'fecha' => today(),
                'logrado' => (bool) $logrado, 'alerta' => $alerta, 'evaluado_por' => $request->user()->id,
            ]);
            $registrados++;
            $alertas += $alerta ? 1 : 0;
        }

        $mensaje = "Evaluación del desarrollo registrada ({$registrados} hitos).";

        return redirect()->route('pacientes.show', [$paciente, 'tab' => 'desarrollo'])
            ->with($alertas ? 'warning' : 'success', $alertas ? "{$mensaje} ALERTA: {$alertas} hito(s) no logrado(s) después de la edad límite." : $mensaje);
    }

    public function createPsc35(Patient $paciente): View|RedirectResponse
    {
        $edad = (int) $paciente->fecha_nacimiento->diffInYears(now());
        if ($edad < Psc35Service::EDAD_MINIMA_ANIOS || $edad > Psc35Service::EDAD_MAXIMA_ANIOS) {
            return back()->with('error', 'El cribado PSC-35 se aplica a pacientes de 4 a 16 años.');
        }

        return view('pacientes.psc35', ['paciente' => $paciente, 'items' => Psc35Service::ITEMS, 'corte' => Psc35Service::puntoCorte($edad)]);
    }

    public function storePsc35(Request $request, Patient $paciente, Psc35Service $servicio): RedirectResponse
    {
        $request->validate(['respuestas' => ['required', 'array', 'size:35'], 'respuestas.*' => ['required', 'in:0,1,2']],
            ['respuestas.size' => 'Debe responder los 35 ítems del cuestionario.']);

        try {
            $resultado = $servicio->evaluar($request->input('respuestas'), (int) $paciente->fecha_nacimiento->diffInYears(now()));
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['respuestas' => $e->getMessage()]);
        }

        Psc35Screening::create($resultado + ['patient_id' => $paciente->id, 'fecha' => today(), 'aplicado_por' => $request->user()->id]);

        return redirect()->route('pacientes.show', [$paciente, 'tab' => 'desarrollo'])
            ->with($resultado['positivo'] ? 'warning' : 'success', 'PSC-35 registrado. '.$resultado['interpretacion']);
    }
}
