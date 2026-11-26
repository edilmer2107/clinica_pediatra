@extends('layouts.app')
@section('title', 'Dispensación')
@section('header', 'Farmacia · Dispensación de recetas')

@section('content')
<div class="card mb-6">
    <div class="card-header">
        <h2 class="card-title">Recetas pendientes de dispensar</h2>
        <form method="GET" class="flex gap-2"><input type="search" name="buscar" value="{{ $buscar }}" placeholder="Código de receta, paciente o HC" class="form-input w-72"><button class="btn-secondary">Buscar</button></form>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Receta</th><th>Fecha</th><th>Paciente</th><th>Médico</th><th>Medicamentos</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse ($pendientes as $r)
                <tr>
                    <td class="font-mono text-xs">{{ $r->codigo }}</td>
                    <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $r->patient->nombre_completo }}<div class="text-xs text-slate-500">{{ $r->patient->historia_clinica }}</div></td>
                    <td>{{ $r->doctor->name }}</td>
                    <td class="text-xs">@foreach ($r->items as $i){{ $i->medication->nombre }} ({{ $i->cantidad_dispensada }}/{{ $i->cantidad }})@if(!$loop->last)<br>@endif @endforeach</td>
                    <td><span class="{{ $r->estado === 'parcial' ? 'badge-yellow' : 'badge-blue' }}">{{ ucfirst($r->estado) }}</span>
                        @if ($r->lista_para_retiro_at)<div class="mt-1 text-xs text-emerald-700">Lista para retiro</div>@endif</td>
                    <td class="text-right whitespace-nowrap">
                        @unless ($r->lista_para_retiro_at)
                            <form method="POST" action="{{ route('dispensaciones.lista', $r) }}" class="inline">@csrf<button class="btn-secondary btn-sm" title="Notificar al tutor">Avisar listo</button></form>
                        @endunless
                        <a href="{{ route('dispensaciones.create', $r) }}" class="btn-primary btn-sm">Dispensar</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-slate-500">No hay recetas pendientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">Dispensaciones recientes</h2></div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Código</th><th>Fecha</th><th>Receta</th><th>Paciente</th><th>Farmacéutico</th><th></th></tr></thead>
            <tbody>
            @forelse ($recientes as $d)
                <tr>
                    <td class="font-mono text-xs">{{ $d->codigo }}</td>
                    <td>{{ $d->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $d->prescription->codigo }}</td>
                    <td>{{ $d->patient->nombre_completo }}</td>
                    <td>{{ $d->pharmacist->name }}</td>
                    <td class="text-right"><a href="{{ route('dispensaciones.show', $d) }}" class="btn-secondary btn-sm">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-6 text-center text-slate-500">Sin dispensaciones registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
