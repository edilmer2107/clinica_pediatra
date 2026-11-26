<?php

namespace App\Http\Controllers\Pacientes;

use App\Http\Controllers\Controller;
use App\Http\Requests\TutorRequest;
use App\Models\Consent;
use App\Models\Patient;
use App\Models\Tutor;
use App\Models\User;
use App\Services\AuditService;
use App\Services\Pacientes\PacienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TutorController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar'));
        $tutores = Tutor::withCount('patients')->with('user')
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w->where('dni', 'like', "%{$buscar}%")
                ->orWhere('nombres', 'ilike', "%{$buscar}%")->orWhere('apellidos', 'ilike', "%{$buscar}%")))
            ->orderBy('apellidos')->paginate(15)->withQueryString();

        return view('pacientes.tutores.index', compact('tutores', 'buscar'));
    }

    public function show(Tutor $tutor): View
    {
        $tutor->load(['patients', 'user', 'consents.patient']);

        return view('pacientes.tutores.show', compact('tutor'));
    }

    public function edit(Tutor $tutor): View
    {
        $this->authorize('pacientes.gestionar');

        return view('pacientes.tutores.edit', compact('tutor'));
    }

    public function update(TutorRequest $request, Tutor $tutor, AuditService $audit): RedirectResponse
    {
        $antes = $tutor->only(array_keys($request->validated()));
        $tutor->update($request->validated());
        $tutor->user?->update(['email' => $tutor->email ?? $tutor->user->email, 'name' => $tutor->nombre_completo]);
        $audit->registrar('editar_tutor', 'pacientes', "Edición del tutor DNI {$tutor->dni}.", $tutor, $antes, $tutor->getChanges());

        return redirect()->route('tutores.show', $tutor)->with('success', 'Datos del tutor actualizados.');
    }

    /** Vincula un tutor (nuevo o existente por DNI) a un paciente. */
    public function vincular(Request $request, Patient $paciente, PacienteService $servicio): RedirectResponse
    {
        $this->authorize('pacientes.gestionar');
        $existente = Tutor::where('dni', $request->input('dni'))->first();

        $reglas = $existente ? ['dni' => ['required', 'regex:/^\d{8}$/']] : TutorRequest::reglas('', false);
        $datos = $request->validate($reglas + [
            'parentesco' => ['required', Rule::in(PacienteController::PARENTESCOS)],
            'es_principal' => ['nullable', 'boolean'],
            'puede_retirar_medicamentos' => ['nullable', 'boolean'],
        ], TutorRequest::mensajes());

        $tutor = $existente ?? Tutor::create(collect($datos)->only(['dni', 'nombres', 'apellidos', 'telefono', 'email', 'direccion'])->all());
        $servicio->vincular($paciente, $tutor, $datos['parentesco'], $request->boolean('es_principal'), $request->boolean('puede_retirar_medicamentos'));

        return back()->with('success', "Tutor {$tutor->nombre_completo} vinculado al paciente.");
    }

    public function desvincular(Patient $paciente, Tutor $tutor, PacienteService $servicio): RedirectResponse
    {
        $this->authorize('pacientes.gestionar');
        $servicio->desvincular($paciente, $tutor);

        return back()->with('success', 'Tutor desvinculado.');
    }

    public function consentimiento(Request $request, Patient $paciente, PacienteService $servicio): RedirectResponse
    {
        $this->authorize('pacientes.gestionar');
        $datos = $request->validate([
            'tutor_id' => ['required', 'exists:tutors,id'],
            'acepta_tratamiento_datos' => ['accepted'],
        ], ['acepta_tratamiento_datos.accepted' => 'El tutor debe aceptar el tratamiento de datos.']);

        $servicio->registrarConsentimiento($paciente, Tutor::findOrFail($datos['tutor_id']), $request->only(['acepta_tratamiento_datos', 'acepta_atencion_medica', 'acepta_notificaciones']));

        return back()->with('success', 'Consentimiento informado registrado.');
    }

    public function revocarConsentimiento(Consent $consentimiento, PacienteService $servicio): RedirectResponse
    {
        $this->authorize('pacientes.gestionar');
        $servicio->revocarConsentimiento($consentimiento);

        return back()->with('success', 'Consentimiento revocado.');
    }

    /** Crea la cuenta de acceso del tutor al portal. */
    public function crearAcceso(Request $request, Tutor $tutor, AuditService $audit): RedirectResponse
    {
        $this->authorize('pacientes.gestionar');
        if ($tutor->user) {
            return back()->with('error', 'El tutor ya tiene una cuenta de acceso al portal.');
        }

        $datos = $request->validate([
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ]);

        $usuario = User::create([
            'name' => $tutor->nombre_completo,
            'email' => Str::lower($datos['email']),
            'password' => $datos['password'],
            'dni' => null,
            'telefono' => $tutor->telefono,
            'activo' => true,
        ]);
        $usuario->assignRole('tutor');
        $tutor->update(['user_id' => $usuario->id, 'email' => $tutor->email ?? $usuario->email]);
        $audit->registrar('crear_acceso_portal', 'portal', "Creación de cuenta de portal para el tutor DNI {$tutor->dni}.", $tutor);

        return back()->with('success', 'Cuenta del portal de tutores creada.');
    }
}
