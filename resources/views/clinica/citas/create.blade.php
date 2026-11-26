@extends('layouts.app')
@section('title', 'Agendar cita')
@section('header', 'Agendar cita')

@section('content')
<form method="POST" action="{{ route('citas.store') }}" class="card max-w-3xl" x-data="{ modalidad: '{{ old('modalidad', 'presencial') }}' }">
    @csrf
    <div class="card-body grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2"><label class="form-label" for="patient_id">Paciente *</label>
            <select id="patient_id" name="patient_id" class="form-input" required>
                <option value="">Seleccione…</option>
                @foreach ($pacientes as $p)<option value="{{ $p->id }}" @selected(old('patient_id', $paciente?->id) == $p->id)>{{ $p->apellidos }}, {{ $p->nombres }} ({{ $p->historia_clinica }})</option>@endforeach
            </select></div>
        <div><label class="form-label" for="doctor_id">Médico *</label><select id="doctor_id" name="doctor_id" class="form-input" required>@foreach ($medicos as $m)<option value="{{ $m->id }}" @selected(old('doctor_id') == $m->id)>{{ $m->name }}</option>@endforeach</select></div>
        <div><label class="form-label" for="modalidad">Modalidad *</label><select id="modalidad" name="modalidad" x-model="modalidad" class="form-input" required><option value="presencial">Presencial</option><option value="telemedicina">Telemedicina</option></select></div>
        <div><label class="form-label" for="fecha">Fecha *</label><input id="fecha" type="date" name="fecha" min="{{ today()->toDateString() }}" value="{{ old('fecha', today()->addDay()->toDateString()) }}" class="form-input" required></div>
        <div><label class="form-label" for="hora">Hora *</label><input id="hora" type="time" name="hora" min="08:00" max="18:45" step="900" value="{{ old('hora', '09:00') }}" class="form-input" required></div>
        <div><label class="form-label" for="duracion_minutos">Duración *</label><select id="duracion_minutos" name="duracion_minutos" class="form-input">@foreach ([15, 20, 30, 45, 60] as $d)<option value="{{ $d }}" @selected(old('duracion_minutos', 30) == $d)>{{ $d }} minutos</option>@endforeach</select></div>
        <div><label class="form-label" for="servicio">Servicio *</label><select id="servicio" name="servicio" class="form-input">@foreach (\App\Models\Appointment::SERVICIOS as $k => $v)<option value="{{ $k }}" @selected(old('servicio') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="md:col-span-2"><label class="form-label" for="motivo">Motivo</label><input id="motivo" name="motivo" value="{{ old('motivo') }}" class="form-input" maxlength="255"></div>
        <p x-show="modalidad === 'telemedicina'" class="alert-info md:col-span-2">Se generará automáticamente un enlace seguro de videollamada que se enviará al tutor.</p>
        <label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" name="notificar" value="1" checked> Enviar confirmación al tutor por correo y SMS</label>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('citas.index') }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Agendar</button></div>
</form>
@endsection
