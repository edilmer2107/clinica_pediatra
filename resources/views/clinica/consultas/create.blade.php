@extends('layouts.app')
@section('title', 'Nueva consulta')
@section('header', 'Historia clínica · '.$paciente->nombre_completo)

@section('content')
<div class="alert-info mb-4"><strong>Plantilla: {{ \App\Services\Clinica\PlantillaHistoriaService::PLANTILLAS[$plantilla] }}</strong> — seleccionada automáticamente por la edad ({{ $paciente->edad_texto }}). @if ($paciente->alergias)<span class="font-semibold text-red-700">Alergias: {{ $paciente->alergias }}</span>@endif</div>
<form method="POST" action="{{ route('consultas.store') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="patient_id" value="{{ $paciente->id }}">
    <input type="hidden" name="appointment_id" value="{{ $cita?->id }}">
    <div class="card"><div class="card-header"><h2 class="card-title">Motivo de consulta</h2></div>
        <div class="card-body grid gap-4 md:grid-cols-3">
            <div><label class="form-label" for="servicio">Servicio *</label><select id="servicio" name="servicio" class="form-input" @disabled($cita)>@foreach (\App\Models\Appointment::SERVICIOS as $k => $v)<option value="{{ $k }}" @selected(old('servicio', $cita?->servicio) === $k)>{{ $v }}</option>@endforeach</select>@if ($cita)<input type="hidden" name="servicio" value="{{ $cita->servicio }}">@endif</div>
            <div class="md:col-span-2"><label class="form-label" for="motivo_consulta">Motivo de consulta *</label><input id="motivo_consulta" name="motivo_consulta" value="{{ old('motivo_consulta', $cita?->motivo) }}" class="form-input" required></div>
            <div class="md:col-span-3"><label class="form-label" for="enfermedad_actual">Enfermedad actual / relato</label><textarea id="enfermedad_actual" name="enfermedad_actual" rows="2" class="form-input">{{ old('enfermedad_actual') }}</textarea></div>
        </div>
    </div>
    <div class="card"><div class="card-header"><h2 class="card-title">Interrogatorio</h2></div>
        <div class="card-body grid gap-4 md:grid-cols-2">
            @foreach ($interrogatorio as $campo => [$etiqueta, $obligatorio])
                <div><label class="form-label" for="i_{{ $campo }}">{{ $etiqueta }} @if($obligatorio)<span class="text-red-600">*</span>@endif</label><textarea id="i_{{ $campo }}" name="interrogatorio[{{ $campo }}]" rows="2" class="form-input" @required($obligatorio)>{{ old("interrogatorio.$campo") }}</textarea></div>
            @endforeach
        </div>
    </div>
    <div class="card"><div class="card-header"><h2 class="card-title">Signos vitales y examen físico segmentado</h2></div>
        <div class="card-body space-y-4">
            <div class="grid gap-4 md:grid-cols-5">
                <div><label class="form-label" for="temperatura">T° (°C)</label><input id="temperatura" type="number" step="0.1" name="temperatura" value="{{ old('temperatura') }}" class="form-input"></div>
                <div><label class="form-label" for="fc">FC (lpm)</label><input id="fc" type="number" name="frecuencia_cardiaca" value="{{ old('frecuencia_cardiaca') }}" class="form-input"></div>
                <div><label class="form-label" for="fr">FR (rpm)</label><input id="fr" type="number" name="frecuencia_respiratoria" value="{{ old('frecuencia_respiratoria') }}" class="form-input"></div>
                <div><label class="form-label" for="sat">SatO₂ (%)</label><input id="sat" type="number" name="saturacion_o2" value="{{ old('saturacion_o2') }}" class="form-input"></div>
                <div><label class="form-label" for="pa">PA (mmHg)</label><input id="pa" name="presion_arterial" value="{{ old('presion_arterial') }}" placeholder="100/60" class="form-input"></div>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($examen as $campo => $etiqueta)
                    <div><label class="form-label" for="e_{{ $campo }}">{{ $etiqueta }}</label><input id="e_{{ $campo }}" name="examen_fisico[{{ $campo }}]" value="{{ old("examen_fisico.$campo", 'Sin alteraciones') }}" class="form-input"></div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="card"><div class="card-header"><h2 class="card-title">Diagnósticos (CIE-10) y plan</h2></div>
        <div class="card-body space-y-3">
            @for ($i = 0; $i < 3; $i++)
                <div class="grid gap-2 md:grid-cols-4">
                    <select name="diagnosticos[{{ $i }}]" class="form-input md:col-span-3" @required($i === 0) aria-label="Diagnóstico {{ $i + 1 }}">
                        <option value="">{{ $i === 0 ? 'Diagnóstico principal *' : 'Diagnóstico secundario (opcional)' }}</option>
                        @foreach ($cie10 as $c)<option value="{{ $c->id }}" @selected(old("diagnosticos.$i") == $c->id)>{{ $c->codigo }} - {{ $c->descripcion }}</option>@endforeach
                    </select>
                    <select name="tipo_diagnostico[{{ $i }}]" class="form-input" aria-label="Tipo de diagnóstico {{ $i + 1 }}"><option value="definitivo">Definitivo</option><option value="presuntivo" @selected(old("tipo_diagnostico.$i") === 'presuntivo')>Presuntivo</option></select>
                </div>
            @endfor
            <div><label class="form-label" for="plan_tratamiento">Plan de tratamiento</label><textarea id="plan_tratamiento" name="plan_tratamiento" rows="2" class="form-input">{{ old('plan_tratamiento') }}</textarea></div>
            <div><label class="form-label" for="indicaciones">Indicaciones al tutor</label><textarea id="indicaciones" name="indicaciones" rows="2" class="form-input">{{ old('indicaciones') }}</textarea></div>
        </div>
    </div>
    <div class="flex justify-end gap-2"><a href="{{ url()->previous() }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Guardar consulta</button></div>
</form>
@endsection
