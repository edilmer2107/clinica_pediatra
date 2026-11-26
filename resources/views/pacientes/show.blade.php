@extends('layouts.app')
@section('title', $paciente->nombre_completo)
@section('header', 'Paciente '.$paciente->historia_clinica)

@section('content')
@php($tab = request('tab', 'resumen'))
<div class="card mb-4">
    <div class="card-body flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">{{ $paciente->nombre_completo }}</h2>
            <p class="text-sm text-slate-600">{{ $paciente->historia_clinica }} · {{ $paciente->sexo === 'M' ? 'Masculino' : 'Femenino' }} · {{ $paciente->edad_texto }} (nac. {{ $paciente->fecha_nacimiento->format('d/m/Y') }}) · DNI {{ $paciente->dni ?? '—' }} · {{ $paciente->seguro ?? 'Sin seguro' }}</p>
            @if ($paciente->alergias)<p class="mt-1 text-sm font-semibold text-red-700">⚠ Alergias: {{ $paciente->alergias }}</p>@endif
            @unless ($consentimiento)<p class="mt-1"><span class="badge-red">Sin consentimiento informado vigente</span></p>@endunless
        </div>
        <div class="flex flex-wrap gap-2">
            @can('pacientes.gestionar')<a href="{{ route('pacientes.edit', $paciente) }}" class="btn-secondary btn-sm">Editar datos</a>@endcan
            @if (Route::has('citas.create'))@can('citas.gestionar')<a href="{{ route('citas.create', ['patient_id' => $paciente->id]) }}" class="btn-secondary btn-sm">Agendar cita</a>@endcan @endif
            @if (Route::has('consultas.create'))@can('consultas.gestionar')<a href="{{ route('consultas.create', ['patient_id' => $paciente->id]) }}" class="btn-primary btn-sm">Nueva consulta</a>@endcan @endif
        </div>
    </div>
    <nav class="flex flex-wrap gap-1 border-t border-slate-200 px-3">
        @foreach (['resumen' => 'Datos y tutores', 'ficha' => 'Ficha clínica', 'crecimiento' => 'Crecimiento (OMS)', 'desarrollo' => 'Desarrollo', 'vacunas' => 'Vacunas', 'consultas' => 'Consultas'] as $k => $v)
            <a href="{{ route('pacientes.show', [$paciente, 'tab' => $k]) }}" class="border-b-2 px-3 py-2 text-sm {{ $tab === $k ? 'border-brand-600 font-semibold text-brand-700' : 'border-transparent text-slate-600 hover:text-slate-900' }}">{{ $v }}</a>
        @endforeach
    </nav>
</div>

@includeWhen($tab === 'resumen', 'pacientes.tabs.resumen')
@includeWhen($tab === 'ficha', 'pacientes.tabs.ficha')
@includeWhen($tab === 'crecimiento', 'pacientes.tabs.crecimiento')
@includeWhen($tab === 'desarrollo', 'pacientes.tabs.desarrollo')
@includeWhen($tab === 'vacunas', 'pacientes.tabs.vacunas')
@includeWhen($tab === 'consultas', 'pacientes.tabs.consultas')
@endsection
