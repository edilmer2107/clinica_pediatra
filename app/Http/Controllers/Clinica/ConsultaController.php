<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Cie10Code;
use App\Models\Consultation;
use App\Models\LabTest;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Service;
use App\Services\Clinica\ConsultaService;
use App\Services\Clinica\PlantillaHistoriaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConsultaController extends Controller
{
    public function index(Request $request): View
    {
        $consultas = Consultation::with(['patient', 'doctor', 'diagnoses.cie10'])
            ->when($request->user()->hasRole('medico') && ! $request->boolean('todas'), fn ($q) => $q->where('doctor_id', $request->user()->id))
            ->when($request->filled('buscar'), fn ($q) => $q->whereHas('patient', fn ($p) => $p->where('nombres', 'ilike', '%'.$request->query('buscar').'%')
                ->orWhere('apellidos', 'ilike', '%'.$request->query('buscar').'%')->orWhere('historia_clinica', 'ilike', '%'.$request->query('buscar').'%')))
            ->latest('fecha')->paginate(15)->withQueryString();

        return view('clinica.consultas.index', compact('consultas'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $request->validate(['patient_id' => ['required', 'exists:patients,id'], 'appointment_id' => ['nullable', 'exists:appointments,id']]);
        $paciente = Patient::with(['pediatricRecord', 'measurements'])->findOrFail($request->integer('patient_id'));
        $cita = $request->filled('appointment_id') ? Appointment::find($request->integer('appointment_id')) : null;
        $plantilla = PlantillaHistoriaService::determinar($paciente);

        return view('clinica.consultas.create', [
            'paciente' => $paciente,
            'cita' => $cita,
            'plantilla' => $plantilla,
            'interrogatorio' => PlantillaHistoriaService::interrogatorio($plantilla),
            'examen' => PlantillaHistoriaService::examenFisico($plantilla),
            'cie10' => Cie10Code::orderBy('codigo')->get(),
        ]);
    }

    public function store(Request $request, ConsultaService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'appointment_id' => ['nullable', 'exists:appointments,id'],
            'servicio' => ['required', Rule::in(array_keys(Appointment::SERVICIOS))],
            'motivo_consulta' => ['required', 'string', 'max:1000'],
            'enfermedad_actual' => ['nullable', 'string', 'max:3000'],
            'interrogatorio' => ['array'], 'interrogatorio.*' => ['nullable', 'string', 'max:1000'],
            'examen_fisico' => ['array'], 'examen_fisico.*' => ['nullable', 'string', 'max:1000'],
            'temperatura' => ['nullable', 'numeric', 'between:30,45'],
            'frecuencia_cardiaca' => ['nullable', 'integer', 'between:30,250'],
            'frecuencia_respiratoria' => ['nullable', 'integer', 'between:5,100'],
            'saturacion_o2' => ['nullable', 'integer', 'between:50,100'],
            'presion_arterial' => ['nullable', 'regex:/^\d{2,3}\/\d{2,3}$/'],
            'diagnosticos' => ['required', 'array', 'min:1'], 'diagnosticos.0' => ['required', 'exists:cie10_codes,id'], 'diagnosticos.*' => ['nullable', 'exists:cie10_codes,id'],
            'tipo_diagnostico' => ['array'], 'tipo_diagnostico.*' => ['nullable', Rule::in(['presuntivo', 'definitivo'])],
            'plan_tratamiento' => ['nullable', 'string', 'max:3000'],
            'indicaciones' => ['nullable', 'string', 'max:3000'],
        ], ['diagnosticos.0.required' => 'Registre al menos el diagnóstico principal (CIE-10).', 'presion_arterial.regex' => 'La presión arterial debe tener el formato 100/60.']);

        $paciente = Patient::findOrFail($datos['patient_id']);
        $cita = ! empty($datos['appointment_id']) ? Appointment::find($datos['appointment_id']) : null;
        $consulta = $servicio->registrar($paciente, $request->user(), $datos, $cita);

        return redirect()->route('consultas.show', $consulta)->with('success', 'Consulta registrada. Puede registrar mediciones, recetas y órdenes de laboratorio.');
    }

    public function show(Consultation $consulta): View
    {
        $consulta->load(['patient.tutors', 'patient.pediatricRecord', 'doctor', 'diagnoses.cie10', 'prescriptions.items.medication', 'labOrders.items.labTest', 'appointment']);
        $plantilla = $consulta->plantilla;

        return view('clinica.consultas.show', [
            'consulta' => $consulta,
            'interrogatorio' => PlantillaHistoriaService::interrogatorio($plantilla),
            'examen' => PlantillaHistoriaService::examenFisico($plantilla),
            'medicamentos' => Medication::where('activo', true)->where('es_vacuna', false)->orderBy('nombre')->get(),
            'examenes' => LabTest::orderBy('tipo')->orderBy('nombre')->get(),
            'procedimientos' => Service::where('tipo', 'procedimiento')->where('activo', true)->orderBy('nombre')->get(),
            'ultimaMedicion' => $consulta->patient->measurements()->reorder('fecha', 'desc')->first(),
        ]);
    }

    public function procedimiento(Request $request, Consultation $consulta, ConsultaService $servicio): RedirectResponse
    {
        $this->autorizarMedico($request, $consulta);
        $datos = $request->validate(['service_id' => ['required', 'exists:services,id'], 'cantidad' => ['required', 'integer', 'min:1', 'max:10']]);
        $servicio->agregarProcedimiento($consulta, Service::findOrFail($datos['service_id']), $datos['cantidad']);

        return back()->with('success', 'Procedimiento cargado a la cuenta del paciente.');
    }

    public function cerrar(Request $request, Consultation $consulta, ConsultaService $servicio): RedirectResponse
    {
        $this->autorizarMedico($request, $consulta);
        $servicio->cerrar($consulta);

        return back()->with('success', 'Consulta cerrada.');
    }

    /** Solo el médico tratante (o el administrador) modifica la consulta. */
    private function autorizarMedico(Request $request, Consultation $consulta): void
    {
        abort_unless($request->user()->hasRole('administrador') || $consulta->doctor_id === $request->user()->id, 403, 'Solo el médico tratante puede modificar esta consulta.');
        abort_if($consulta->estado === 'cerrada', 422, 'La consulta está cerrada.');
    }
}
