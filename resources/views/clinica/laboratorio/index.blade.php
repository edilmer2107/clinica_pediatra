@extends('layouts.app')
@section('title', 'Laboratorio')
@section('header', 'Laboratorio e imágenes')

@section('content')
<div class="card">
    <div class="card-header"><form method="GET" class="flex gap-2"><select name="estado" class="form-input w-48">@foreach (['solicitada' => 'Pendientes', 'completada' => 'Completadas', 'todas' => 'Todas'] as $k => $v)<option value="{{ $k }}" @selected(request('estado', 'solicitada') === $k)>{{ $v }}</option>@endforeach</select><button class="btn-secondary">Filtrar</button></form></div>
    <table class="table">
        <thead><tr><th>Orden</th><th>Fecha</th><th>Paciente</th><th>Médico</th><th>Exámenes</th><th>Prioridad</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($ordenes as $o)
            <tr><td class="font-mono text-xs">{{ $o->codigo }}</td><td>{{ $o->created_at->format('d/m/Y H:i') }}</td><td>{{ $o->patient->nombre_completo }}</td><td>{{ $o->doctor->name }}</td><td class="text-xs">{{ $o->items->pluck('labTest.nombre')->implode(', ') }}</td>
                <td>{!! $o->prioridad === 'urgente' ? '<span class="badge-red">Urgente</span>' : 'Rutina' !!}</td>
                <td><span class="{{ $o->estado === 'completada' ? 'badge-green' : 'badge-yellow' }}">{{ ucfirst($o->estado) }}</span> @if ($o->tieneAnormales())<span class="badge-red">Anormal</span>@endif</td>
                <td class="text-right"><a href="{{ route('laboratorio.show', $o) }}" class="btn-secondary btn-sm">{{ $o->estado === 'completada' ? 'Ver' : 'Registrar' }}</a></td></tr>
        @empty
            <tr><td colspan="8" class="py-6 text-center text-slate-500">Sin órdenes.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $ordenes->links() }}</div>
</div>
@endsection
