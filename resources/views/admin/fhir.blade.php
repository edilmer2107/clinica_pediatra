@extends('layouts.app')
@section('title', 'Interoperabilidad FHIR')
@section('header', 'Interoperabilidad · HL7 FHIR R4')

@section('content')
<div class="grid gap-4 lg:grid-cols-2">
    <div class="card"><div class="card-header"><h2 class="card-title">Exportar historia clínica (Bundle FHIR)</h2></div>
        <div class="card-body space-y-3 text-sm">
            <p class="text-slate-600">Genera un Bundle JSON con los recursos Patient, RelatedPerson (tutores), Observation (peso, talla, PC, IMC y percentiles con códigos LOINC), Immunization (CVX), Condition (CIE-10) y MedicationRequest.</p>
            <form method="GET" x-data="{ id: '' }" @submit.prevent="if (id) window.location = '{{ url('fhir/pacientes') }}/' + id" class="flex gap-2">
                <select x-model="id" class="form-input" required aria-label="Paciente"><option value="">Seleccione paciente…</option>@foreach ($pacientes as $p)<option value="{{ $p->id }}">{{ $p->apellidos }}, {{ $p->nombres }} ({{ $p->historia_clinica }})</option>@endforeach</select>
                <button class="btn-primary">Exportar JSON</button>
            </form>
        </div>
    </div>
    @can('fhir.importar')
    <div class="card"><div class="card-header"><h2 class="card-title">Importar Bundle FHIR</h2></div>
        <form method="POST" action="{{ route('fhir.importar') }}" enctype="multipart/form-data" class="card-body space-y-3 text-sm">@csrf
            <p class="text-slate-600">Importa un paciente (identificado por DNI) con sus tutores y mediciones antropométricas (LOINC 29463-7, 8302-2, 9843-4). Los percentiles se recalculan con los patrones OMS.</p>
            <input type="file" name="archivo" accept=".json,application/json,application/fhir+json" class="form-input" required aria-label="Archivo JSON">
            <button class="btn-primary">Importar</button>
        </form>
    </div>
    @endcan
</div>
@endsection
