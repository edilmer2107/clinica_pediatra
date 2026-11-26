<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pacientes.gestionar');
    }

    public function rules(): array
    {
        $paciente = $this->route('paciente');
        $alta = $paciente === null
            ? TutorRequest::reglas('tutor.', false) + [
                'parentesco' => ['required', Rule::in(\App\Http\Controllers\Pacientes\PacienteController::PARENTESCOS)],
                'acepta_tratamiento_datos' => ['accepted'],
            ]
            : [];

        return $alta + [
            'dni' => ['nullable', 'regex:/^\d{8}$/', Rule::unique('patients', 'dni')->ignore($paciente)],
            'nombres' => ['required', 'string', 'max:80', 'regex:/^[\pL\s\'\-]+$/u'],
            'apellidos' => ['required', 'string', 'max:80', 'regex:/^[\pL\s\'\-]+$/u'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today', 'after:'.now()->subYears(18)->toDateString()],
            'sexo' => ['required', Rule::in(['M', 'F'])],
            'grupo_sanguineo' => ['nullable', Rule::in(['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'])],
            'alergias' => ['nullable', 'string', 'max:500'],
            'seguro' => ['nullable', Rule::in(['SIS', 'EsSalud', 'Privado', 'Ninguno'])],
            'direccion' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return TutorRequest::mensajes('tutor.') + [
            'acepta_tratamiento_datos.accepted' => 'Debe registrar el consentimiento informado del tutor.',
            'dni.regex' => 'El DNI debe tener exactamente 8 dígitos.',
            'fecha_nacimiento.after' => 'El paciente debe ser menor de 18 años (clínica pediátrica).',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'nombres.regex' => 'Los nombres solo pueden contener letras.',
            'apellidos.regex' => 'Los apellidos solo pueden contener letras.',
        ];
    }
}
