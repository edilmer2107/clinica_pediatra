<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Models\Immunization;
use App\Models\Lot;
use App\Models\Patient;
use App\Models\VaccineScheduleDose;
use App\Services\Farmacia\InventarioService;
use App\Services\Pacientes\VacunacionService;
use App\Services\Portal\NotificacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VacunacionController extends Controller
{
    public function index(Request $request): View
    {
        $recientes = Immunization::with(['patient', 'vaccine', 'scheduleDose', 'lot', 'aplicadoPor'])
            ->latest('fecha_aplicacion')->latest('id')->limit(20)->get();

        return view('pacientes.vacunacion.index', compact('recientes'));
    }

    public function create(Patient $paciente, VaccineScheduleDose $dosis, InventarioService $inventario): View
    {
        $dosis->load('vaccine.medication');
        $lotes = $dosis->vaccine->medication ? $inventario->lotesFefo($dosis->vaccine->medication) : collect();

        return view('pacientes.vacunacion.aplicar', compact('paciente', 'dosis', 'lotes'));
    }

    public function store(Request $request, Patient $paciente, VaccineScheduleDose $dosis, VacunacionService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'lot_id' => ['required', 'integer', 'exists:lots,id'],
            'fecha_aplicacion' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:'.$paciente->fecha_nacimiento->toDateString()],
            'sitio_aplicacion' => ['required', 'string', 'max:40'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        $servicio->aplicar($paciente, $dosis, Lot::findOrFail($datos['lot_id']), $request->user(), $datos);

        return redirect()->route('pacientes.show', [$paciente, 'tab' => 'vacunas'])
            ->with('success', "Vacuna {$dosis->vaccine->nombre} ({$dosis->nombre_dosis}) registrada. Se descontó 1 dosis del inventario.");
    }

    public function recordatorio(Patient $paciente, VacunacionService $servicio, NotificacionService $notificaciones): RedirectResponse
    {
        $this->authorize('notificaciones.enviar');
        $vencidas = $servicio->dosisVencidas($paciente);
        if ($vencidas === []) {
            return back()->with('warning', 'El paciente no tiene vacunas vencidas.');
        }
        $enviadas = $notificaciones->vacunasVencidas($paciente, $vencidas);

        return back()->with($enviadas ? 'success' : 'warning', $enviadas
            ? "Recordatorio enviado ({$enviadas} mensajes)."
            : 'No se envió: los tutores no tienen consentimiento de notificaciones o datos de contacto.');
    }
}
