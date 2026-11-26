@extends('layouts.app')
@section('title', 'Recetas')
@section('header', 'Recetas electrónicas')

@section('content')
<div class="card">
    <div class="card-header"><form method="GET" class="flex gap-2"><input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Código o apellido" class="form-input w-60">
        <select name="estado" class="form-input w-40"><option value="">Todos</option>@foreach (['emitida', 'parcial', 'dispensada', 'anulada'] as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ ucfirst($e) }}</option>@endforeach</select><button class="btn-secondary">Filtrar</button></form></div>
    <table class="table">
        <thead><tr><th>Código</th><th>Fecha</th><th>Paciente</th><th>Médico</th><th>Medicamentos</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($recetas as $r)
            <tr><td class="font-mono text-xs">{{ $r->codigo }}</td><td>{{ $r->created_at->format('d/m/Y') }}</td><td>{{ $r->patient->nombre_completo }}</td><td>{{ $r->doctor->name }}</td>
                <td class="text-xs">{{ $r->items->pluck('medication.nombre')->implode(', ') }} @if ($r->items->contains(fn ($i) => $i->alertas))<span class="text-red-700">⚠</span>@endif</td>
                <td><span class="badge-gray">{{ ucfirst($r->estado) }}</span></td><td class="text-right"><a href="{{ route('recetas.show', $r) }}" class="btn-secondary btn-sm">Ver</a></td></tr>
        @empty
            <tr><td colspan="7" class="py-6 text-center text-slate-500">Sin recetas.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $recetas->links() }}</div>
</div>
@endsection
