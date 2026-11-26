@extends('layouts.app')
@section('title', 'Ficha clínica')
@section('header', 'Ficha clínica pediátrica · '.$paciente->nombre_completo)

@section('content')
@php($v = fn ($campo, $def = null) => old($campo, $ficha?->$campo ?? $def))
<form method="POST" action="{{ route('pacientes.ficha.update', $paciente) }}" class="space-y-4">
    @csrf @method('PUT')
    <div class="card"><div class="card-header"><h2 class="card-title">Antecedentes prenatales</h2></div>
        <div class="card-body grid gap-4 md:grid-cols-3">
            <div><label class="form-label" for="numero_controles_prenatales">N° de controles prenatales *</label><input id="numero_controles_prenatales" type="number" min="0" max="20" name="numero_controles_prenatales" value="{{ $v('numero_controles_prenatales') }}" class="form-input" required></div>
            <div><label class="form-label" for="edad_materna">Edad materna (años) *</label><input id="edad_materna" type="number" min="10" max="60" name="edad_materna" value="{{ $v('edad_materna') }}" class="form-input" required></div>
            <div><label class="form-label" for="suplemento">Suplemento de hierro / ácido fólico *</label><select id="suplemento" name="suplemento_hierro_acido_folico" class="form-input" required><option value="1" @selected($v('suplemento_hierro_acido_folico') == 1)>Sí</option><option value="0" @selected($v('suplemento_hierro_acido_folico') === 0 || $v('suplemento_hierro_acido_folico') === '0' || $v('suplemento_hierro_acido_folico') === false)>No</option></select></div>
            <div class="md:col-span-3"><label class="form-label" for="patologias_embarazo">Patologías durante el embarazo * <span class="text-xs text-slate-500">(escriba "Ninguna" si no hubo)</span></label><textarea id="patologias_embarazo" name="patologias_embarazo" rows="2" class="form-input" required>{{ $v('patologias_embarazo') }}</textarea></div>
        </div>
    </div>
    <div class="card"><div class="card-header"><h2 class="card-title">Antecedentes natales</h2></div>
        <div class="card-body grid gap-4 md:grid-cols-4">
            <div><label class="form-label" for="eg">Edad gestacional (semanas) *</label><input id="eg" type="number" min="22" max="44" name="edad_gestacional_semanas" value="{{ $v('edad_gestacional_semanas') }}" class="form-input" required></div>
            <div><label class="form-label" for="tipo_parto">Tipo de parto *</label><select id="tipo_parto" name="tipo_parto" class="form-input" required>@foreach (['eutocico' => 'Eutócico', 'cesarea' => 'Cesárea', 'instrumentado' => 'Instrumentado'] as $k => $t)<option value="{{ $k }}" @selected($v('tipo_parto') === $k)>{{ $t }}</option>@endforeach</select></div>
            <div><label class="form-label" for="peso_nacer_kg">Peso al nacer (kg) *</label><input id="peso_nacer_kg" type="number" step="0.01" min="0.4" max="6.5" name="peso_nacer_kg" value="{{ $v('peso_nacer_kg') }}" class="form-input" required></div>
            <div><label class="form-label" for="talla_nacer_cm">Talla al nacer (cm) *</label><input id="talla_nacer_cm" type="number" step="0.1" min="25" max="65" name="talla_nacer_cm" value="{{ $v('talla_nacer_cm') }}" class="form-input" required></div>
            <div><label class="form-label" for="pc_nacer">PC al nacer (cm)</label><input id="pc_nacer" type="number" step="0.1" min="20" max="45" name="perimetro_cefalico_nacer_cm" value="{{ $v('perimetro_cefalico_nacer_cm') }}" class="form-input"></div>
            <div><label class="form-label" for="apgar_1">APGAR 1 min *</label><input id="apgar_1" type="number" min="0" max="10" name="apgar_1" value="{{ $v('apgar_1') }}" class="form-input" required></div>
            <div><label class="form-label" for="apgar_5">APGAR 5 min *</label><input id="apgar_5" type="number" min="0" max="10" name="apgar_5" value="{{ $v('apgar_5') }}" class="form-input" required></div>
            <div class="md:col-span-4"><label class="form-label" for="complicaciones_parto">Complicaciones del parto *</label><textarea id="complicaciones_parto" name="complicaciones_parto" rows="2" class="form-input" required>{{ $v('complicaciones_parto') }}</textarea></div>
        </div>
    </div>
    <div class="card"><div class="card-header"><h2 class="card-title">Antecedentes posnatales</h2></div>
        <div class="card-body grid gap-4 md:grid-cols-2">
            <div><label class="form-label" for="lactancia">Lactancia *</label><select id="lactancia" name="lactancia" class="form-input" required>@foreach (['exclusiva' => 'Materna exclusiva', 'mixta' => 'Mixta', 'formula' => 'Fórmula'] as $k => $t)<option value="{{ $k }}" @selected($v('lactancia') === $k)>{{ $t }}</option>@endforeach</select></div>
            <div><label class="form-label" for="tamizaje">Tamizaje neonatal *</label><select id="tamizaje" name="tamizaje_neonatal" class="form-input" required><option value="1" @selected($v('tamizaje_neonatal') == 1)>Sí</option><option value="0" @selected((string) $v('tamizaje_neonatal') === '0')>No</option></select></div>
            <div><label class="form-label" for="hospitalizaciones">Hospitalizaciones *</label><textarea id="hospitalizaciones" name="hospitalizaciones" rows="2" class="form-input" required>{{ $v('hospitalizaciones') }}</textarea></div>
            <div><label class="form-label" for="enfermedades_previas">Enfermedades previas *</label><textarea id="enfermedades_previas" name="enfermedades_previas" rows="2" class="form-input" required>{{ $v('enfermedades_previas') }}</textarea></div>
            <div class="md:col-span-2"><label class="form-label" for="antecedentes_familiares">Antecedentes familiares *</label><textarea id="antecedentes_familiares" name="antecedentes_familiares" rows="2" class="form-input" required>{{ $v('antecedentes_familiares') }}</textarea></div>
        </div>
    </div>
    <div class="flex justify-end gap-2"><a href="{{ route('pacientes.show', [$paciente, 'tab' => 'ficha']) }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Guardar ficha</button></div>
</form>
@endsection
