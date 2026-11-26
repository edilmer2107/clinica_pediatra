<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pacientes.gestionar');
    }

    /** Reglas reutilizables (con prefijo opcional, p. ej. "tutor."). */
    public static function reglas(string $prefijo = '', $ignorar = null): array
    {
        return [
            $prefijo.'dni' => array_values(array_filter(['required', 'regex:/^\d{8}$/', $ignorar !== false ? Rule::unique('tutors', 'dni')->ignore($ignorar) : null])),
            $prefijo.'nombres' => ['required', 'string', 'max:80', 'regex:/^[\pL\s\'\-]+$/u'],
            $prefijo.'apellidos' => ['required', 'string', 'max:80', 'regex:/^[\pL\s\'\-]+$/u'],
            $prefijo.'telefono' => ['required', 'regex:/^9\d{8}$/'],
            $prefijo.'email' => ['nullable', 'email', 'max:120'],
            $prefijo.'direccion' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function rules(): array
    {
        return self::reglas('', $this->route('tutor'));
    }

    public function messages(): array
    {
        return self::mensajes();
    }

    public static function mensajes(string $prefijo = ''): array
    {
        return [
            $prefijo.'dni.regex' => 'El DNI del tutor debe tener exactamente 8 dígitos.',
            $prefijo.'dni.unique' => 'Ya existe un tutor con ese DNI.',
            $prefijo.'telefono.regex' => 'El celular debe tener 9 dígitos y empezar con 9.',
        ];
    }
}
