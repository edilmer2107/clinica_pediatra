@extends('layouts.app')
@section('title', 'ESAVI')
@section('header', 'Eventos supuestamente atribuidos a la vacunación (ESAVI)')

@section('content')
<div class="card">
    <table class="table">
        <thead><tr><th>Fecha síntomas</th><th>Paciente</th><th>Vacuna</th><th>Lote</th><th>Gravedad</th><th>Desenlace</th><th></th></tr></thead>
        <tbody>
        @forelse ($reportes as $r)
            <tr><td>{{ $r->fecha_inicio_sintomas->format('d/m/Y H:i') }}</td><td>{{ $r->patient->nombre_completo }}</td><td>{{ $r->immunization->vaccine->nombre }}</td><td class="font-mono text-xs">{{ $r->immunization->lot->numero_lote }}</td>
                <td><span class="{{ $r->gravedad === 'grave' ? 'badge-red' : ($r->gravedad === 'moderado' ? 'badge-yellow' : 'badge-gray') }}">{{ ucfirst($r->gravedad) }}</span></td><td>{{ str_replace('_', ' ', $r->desenlace) }}</td>
                <td class="text-right"><a href="{{ route('esavi.show', $r) }}" class="btn-secondary btn-sm">Ver ficha</a></td></tr>
        @empty
            <tr><td colspan="7" class="py-6 text-center text-slate-500">Sin ESAVI registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $reportes->links() }}</div>
</div>
@endsection
