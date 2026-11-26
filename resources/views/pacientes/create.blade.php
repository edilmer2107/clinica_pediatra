@extends('layouts.app')
@section('title', 'Registrar paciente')
@section('header', 'Registrar paciente pediátrico')

@section('content')
<form method="POST" action="{{ route('pacientes.store') }}" class="space-y-4">
    @csrf
    <div class="card"><div class="card-header"><h2 class="card-title">Datos del paciente</h2></div><div class="card-body">@include('pacientes._form_paciente', ['paciente' => null])</div></div>

    <div class="card">
        <div class="card-header"><h2 class="card-title">Tutor principal</h2><span class="text-xs text-slate-500">Si el DNI ya existe, se vincula el tutor registrado.</span></div>
        <div class="card-body grid gap-4 md:grid-cols-3">
            <div><label class="form-label" for="tutor_dni">DNI *</label><input id="tutor_dni" name="tutor[dni]" value="{{ old('tutor.dni') }}" class="form-input" required maxlength="8" pattern="\d{8}" inputmode="numeric"></div>
            <div><label class="form-label" for="tutor_nombres">Nombres *</label><input id="tutor_nombres" name="tutor[nombres]" value="{{ old('tutor.nombres') }}" class="form-input" required></div>
            <div><label class="form-label" for="tutor_apellidos">Apellidos *</label><input id="tutor_apellidos" name="tutor[apellidos]" value="{{ old('tutor.apellidos') }}" class="form-input" required></div>
            <div><label class="form-label" for="parentesco">Parentesco *</label><select id="parentesco" name="parentesco" class="form-input" required>@foreach (\App\Http\Controllers\Pacientes\PacienteController::PARENTESCOS as $p)<option @selected(old('parentesco') === $p)>{{ $p }}</option>@endforeach</select></div>
            <div><label class="form-label" for="tutor_telefono">Celular *</label><input id="tutor_telefono" name="tutor[telefono]" value="{{ old('tutor.telefono') }}" class="form-input" required maxlength="9" placeholder="9XXXXXXXX" inputmode="numeric"></div>
            <div><label class="form-label" for="tutor_email">Correo electrónico</label><input id="tutor_email" type="email" name="tutor[email]" value="{{ old('tutor.email') }}" class="form-input"></div>
            <div class="md:col-span-3"><label class="form-label" for="tutor_direccion">Dirección</label><input id="tutor_direccion" name="tutor[direccion]" value="{{ old('tutor.direccion') }}" class="form-input"></div>
        </div>
    </div>

    @include('pacientes._consentimiento')

    <div class="flex justify-end gap-2"><a href="{{ route('pacientes.index') }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Registrar paciente</button></div>
</form>
@endsection
