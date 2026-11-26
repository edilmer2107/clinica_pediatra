@extends('layouts.app')
@section('title', 'Vacunación')
@section('header', 'Vacunación')

@section('content')
<div class="mb-4 flex flex-wrap gap-2">
    <a href="{{ route('pacientes.index') }}" class="btn-primary">Buscar paciente para vacunar</a>
    @can('esavi.registrar')<a href="{{ route('esavi.index') }}" class="btn-secondary">Reportes ESAVI</a>@endcan
    @can('reportes.clinicos')
        <a href="{{ route('reportes.show', 'vacunas-pendientes') }}" class="btn-secondary">Pacientes con vacunas pendientes/vencidas</a>
        <a href="{{ route('reportes.show', 'cobertura-vacunacion') }}" class="btn-secondary">Cobertura de vacunación</a>
    @endcan
</div>
<div class="card">
    <div class="card-header"><h2 class="card-title">Últimas vacunas aplicadas</h2></div>
    <table class="table">
        <thead><tr><th>Fecha</th><th>Paciente</th><th>Vacuna</th><th>Dosis</th><th>Lote</th><th>Aplicó</th></tr></thead>
        <tbody>
        @forelse ($recientes as $i)
            <tr><td>{{ $i->fecha_aplicacion->format('d/m/Y') }}</td><td><a class="text-brand-700 hover:underline" href="{{ route('pacientes.show', [$i->patient, 'tab' => 'vacunas']) }}">{{ $i->patient->nombre_completo }}</a></td><td>{{ $i->vaccine->nombre }}</td><td class="text-xs">{{ $i->scheduleDose?->nombre_dosis }}</td><td class="font-mono text-xs">{{ $i->lot->numero_lote }}</td><td class="text-xs">{{ $i->aplicadoPor?->name }}</td></tr>
        @empty
            <tr><td colspan="6" class="py-6 text-center text-slate-500">Sin vacunas registradas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
