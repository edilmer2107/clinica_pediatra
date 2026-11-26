<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinica\CitaService;
use App\Services\Portal\NotificacionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CitaController extends Controller
{
    public function index(Request $request): View
    {
        $fecha = $request->filled('fecha') ? Carbon::parse($request->query('fecha')) : today();
        $medicoId = $request->integer('doctor_id') ?: ($request->user()->hasRole('medico') ? $request->user()->id : null);

        $citas = Appointment::with(['patient', 'doctor', 'tutor'])
            ->whereDate('fecha_hora', $fecha)
            ->when($medicoId, fn ($q) => $q->where('doctor_id', $medicoId))
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->query('estado')))
            ->orderBy('fecha_hora')->get();

        $medicos = User::role('medico')->orderBy('name')->get();

        return view('clinica.citas.index', compact('citas', 'fecha', 'medicos', 'medicoId'));
    }

    public function create(Request $request): View
    {
        $this->authorize('citas.gestionar');

        return view('clinica.citas.create', [
            'paciente' => $request->filled('patient_id') ? Patient::with('tutors')->find($request->integer('patient_id')) : null,
            'pacientes' => Patient::orderBy('apellidos')->get(['id', 'nombres', 'apellidos', 'historia_clinica']),
            'medicos' => User::role('medico')->where('activo', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CitaService $servicio, NotificacionService $notificaciones): RedirectResponse
    {
        $this->authorize('citas.gestionar');
        $datos = $request->validate([
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['required', 'exists:users,id'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'duracion_minutos' => ['required', 'integer', Rule::in([15, 20, 30, 45, 60])],
            'modalidad' => ['required', Rule::in(['presencial', 'telemedicina'])],
            'servicio' => ['required', Rule::in(array_keys(Appointment::SERVICIOS))],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);
        $datos['fecha_hora'] = Carbon::parse($datos['fecha'].' '.$datos['hora']);

        $cita = $servicio->agendar($datos);

        $mensaje = 'Cita agendada para el '.$cita->fecha_hora->format('d/m/Y H:i').'.';
        if ($request->boolean('notificar')) {
            $mensaje .= ' Notificaciones enviadas: '.$notificaciones->recordatorioCita($cita).'.';
        }

        return redirect()->route('citas.index', ['fecha' => $cita->fecha_hora->toDateString(), 'doctor_id' => $cita->doctor_id])->with('success', $mensaje);
    }

    public function cancelar(Request $request, Appointment $cita, CitaService $servicio): RedirectResponse
    {
        $this->authorize('citas.gestionar');
        $datos = $request->validate(['motivo' => ['required', 'string', 'max:255']]);
        $servicio->cancelar($cita, $datos['motivo']);

        return back()->with('success', 'Cita cancelada.');
    }

    public function editReprogramar(Appointment $cita): View
    {
        $this->authorize('citas.gestionar');

        return view('clinica.citas.reprogramar', ['cita' => $cita->load('patient', 'doctor'), 'politica' => CitaService::dentroDePolitica($cita)]);
    }

    public function reprogramar(Request $request, Appointment $cita, CitaService $servicio): RedirectResponse
    {
        $this->authorize('citas.gestionar');
        $datos = $request->validate([
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora' => ['required', 'date_format:H:i'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);
        $nueva = $servicio->reprogramar($cita, Carbon::parse($datos['fecha'].' '.$datos['hora']), $datos['motivo']);

        return redirect()->route('citas.index', ['fecha' => $nueva->fecha_hora->toDateString()])->with('success', 'Cita reprogramada para el '.$nueva->fecha_hora->format('d/m/Y H:i').'.');
    }

    public function confirmar(Appointment $cita): RedirectResponse
    {
        $this->authorize('citas.gestionar');
        abort_unless($cita->estado === 'programada', 422, 'Solo se confirman citas programadas.');
        $cita->update(['estado' => 'confirmada']);

        return back()->with('success', 'Cita confirmada.');
    }

    public function noAsistio(Appointment $cita, CitaService $servicio): RedirectResponse
    {
        $this->authorize('citas.gestionar');
        $servicio->marcarNoAsistio($cita);

        return back()->with('success', 'Cita marcada como no asistida.');
    }

    public function recordatorio(Appointment $cita, NotificacionService $notificaciones): RedirectResponse
    {
        $this->authorize('citas.gestionar');
        $enviadas = $notificaciones->recordatorioCita($cita);

        return back()->with($enviadas ? 'success' : 'warning', $enviadas ? "Recordatorio enviado ({$enviadas} mensajes)." : 'No se envió: el tutor no autorizó notificaciones o no tiene contacto.');
    }
}
