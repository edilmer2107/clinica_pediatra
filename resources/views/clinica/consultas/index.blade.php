@extends('layouts.app')
@section('title', 'Consultas')
@section('header', 'Consultas médicas')

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="flex gap-2"><input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Paciente o HC" class="form-input w-64">
            @role('medico')<label class="flex items-center gap-1 text-sm"><input type="checkbox" name="todas" value="1" @checked(request('todas'))> Todas</label>@endrole
            <button class="btn-secondary">Buscar</button></form>
        <span class="text-xs text-slate-500">Para una nueva consulta, ábrala desde la agenda o la ficha del paciente.</span>
    </div>
    <table class="table">
        <thead><tr><th>Fecha</th><th>Paciente</th><th>Médico</th><th>Plantilla</th><th>Diagnóstico principal</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($consultas as $c)
            <tr><td>{{ $c->fecha->format('d/m/Y H:i') }}</td><td>{{ $c->patient->nombre_completo }}</td><td>{{ $c->doctor->name }}</td><td>{{ ucfirst($c->plantilla) }}</td>
                <td class="text-xs">{{ $c->diagnoses->firstWhere('principal', true)?->cie10?->codigo }} {{ $c->diagnoses->firstWhere('principal', true)?->cie10?->descripcion }}</td>
                <td><span class="{{ $c->estado === 'cerrada' ? 'badge-gray' : 'badge-green' }}">{{ ucfirst($c->estado) }}</span></td>
                <td class="text-right"><a href="{{ route('consultas.show', $c) }}" class="btn-secondary btn-sm">Abrir</a></td></tr>
        @empty
            <tr><td colspan="7" class="py-6 text-center text-slate-500">Sin consultas.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $consultas->links() }}</div>
</div>
@endsection
