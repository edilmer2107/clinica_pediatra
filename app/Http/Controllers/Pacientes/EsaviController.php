<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Models\EsaviReport;
use App\Models\Immunization;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Eventos Supuestamente Atribuidos a la Vacunación o Inmunización (ESAVI).
 */
class EsaviController extends Controller
{
    public function index(): View
    {
        $reportes = EsaviReport::with(['patient', 'immunization.vaccine', 'immunization.lot', 'reportadoPor'])->latest()->paginate(15);

        return view('pacientes.esavi.index', compact('reportes'));
    }

    public function create(Immunization $inmunizacion): View
    {
        $inmunizacion->load(['patient', 'vaccine', 'lot', 'scheduleDose']);

        return view('pacientes.esavi.create', compact('inmunizacion'));
    }

    public function store(Request $request, Immunization $inmunizacion, AuditService $audit): RedirectResponse
    {
        $datos = $request->validate([
            'fecha_inicio_sintomas' => ['required', 'date', 'before_or_equal:now', 'after_or_equal:'.$inmunizacion->fecha_aplicacion->toDateString()],
            'sintomas' => ['required', 'string', 'max:1000'],
            'gravedad' => ['required', Rule::in(['leve', 'moderado', 'grave'])],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'tratamiento' => ['nullable', 'string', 'max:1000'],
            'desenlace' => ['required', Rule::in(['recuperado', 'en_seguimiento', 'hospitalizado', 'secuelas', 'fallecido'])],
        ], ['fecha_inicio_sintomas.after_or_equal' => 'Los síntomas no pueden iniciar antes de la vacunación.']);

        $esavi = EsaviReport::create($datos + [
            'immunization_id' => $inmunizacion->id,
            'patient_id' => $inmunizacion->patient_id,
            'notificado_at' => now(),
            'reportado_por' => $request->user()->id,
        ]);
        $audit->registrar('registrar_esavi', 'pacientes', "ESAVI {$esavi->gravedad} tras {$inmunizacion->vaccine->nombre} lote {$inmunizacion->lot->numero_lote}.", $esavi);

        return redirect()->route('esavi.show', $esavi)->with('success', 'ESAVI registrado. Imprima o exporte la ficha para la notificación a la autoridad sanitaria.');
    }

    public function show(EsaviReport $esavi): View
    {
        $esavi->load(['patient.tutors', 'immunization.vaccine', 'immunization.lot', 'immunization.scheduleDose', 'immunization.aplicadoPor', 'reportadoPor']);

        return view('pacientes.esavi.show', compact('esavi'));
    }
}
