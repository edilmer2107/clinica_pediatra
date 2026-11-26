@extends('layouts.app')
@section('title', 'Citas')
@section('header', 'Agenda de citas')

@section('content')
<div class="card mb-4">
    <form method="GET" class="card-body flex flex-wrap items-end gap-3">
        <div><label class="form-label" for="fecha">Fecha</label><input id="fecha" type="date" name="fecha" value="{{ $fecha->toDateString() }}" class="form-input"></div>
        <div><label class="form-label" for="doctor_id">Médico</label><select id="doctor_id" name="doctor_id" class="form-input w-60"><option value="">Todos</option>@foreach ($medicos as $m)<option value="{{ $m->id }}" @selected($medicoId == $m->id)>{{ $m->name }}</option>@endforeach</select></div>
        <div><label class="form-label" for="estado">Estado</label><select id="estado" name="estado" class="form-input w-44"><option value="">Todos</option>@foreach (\App\Models\Appointment::ESTADOS as $k => $v)<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>@endforeach</select></div>
        <button class="btn-secondary">Ver</button>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('citas.index', ['fecha' => $fecha->copy()->subDay()->toDateString(), 'doctor_id' => $medicoId]) }}" class="btn-secondary">← Día anterior</a>
            <a href="{{ route('citas.index', ['fecha' => $fecha->copy()->addDay()->toDateString(), 'doctor_id' => $medicoId]) }}" class="btn-secondary">Día siguiente →</a>
            @can('citas.gestionar')<a href="{{ route('citas.create') }}" class="btn-primary">Agendar cita</a>@endcan
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header"><h2 class="card-title">{{ ucfirst($fecha->translatedFormat('l d \d\e F \d\e Y')) }} · {{ $citas->count() }} cita(s)</h2></div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Hora</th><th>Paciente</th><th>Médico</th><th>Servicio</th><th>Modalidad</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
            <tbody>
            @forelse ($citas as $c)
                <tr>
                    <td class="font-semibold">{{ $c->fecha_hora->format('H:i') }}<div class="text-xs font-normal text-slate-500">{{ $c->duracion_minutos }} min</div></td>
                    <td><a href="{{ route('pacientes.show', $c->patient) }}" class="text-brand-700 hover:underline">{{ $c->patient->nombre_completo }}</a><div class="text-xs text-slate-500">{{ $c->motivo }}</div></td>
                    <td>{{ $c->doctor->name }}</td>
                    <td>{{ \App\Models\Appointment::SERVICIOS[$c->servicio] }}</td>
                    <td>@if ($c->modalidad === 'telemedicina')<span class="badge-blue">Telemedicina</span>@if ($c->enlace_telemedicina && $c->esModificable())<a href="{{ $c->enlace_telemedicina }}" target="_blank" rel="noopener noreferrer" class="mt-1 block text-xs text-brand-700 underline">Abrir videollamada</a>@endif @else<span class="badge-gray">Presencial</span>@endif</td>
                    <td>@php($cls = ['programada' => 'badge-blue', 'confirmada' => 'badge-green', 'atendida' => 'badge-gray', 'cancelada' => 'badge-red', 'no_asistio' => 'badge-yellow', 'reprogramada' => 'badge-gray'])<span class="{{ $cls[$c->estado] }}">{{ \App\Models\Appointment::ESTADOS[$c->estado] }}</span>
                        @if ($c->recordatorio_enviado_at)<div class="text-xs text-slate-500">Recordatorio {{ $c->recordatorio_enviado_at->format('d/m H:i') }}</div>@endif</td>
                    <td class="whitespace-nowrap text-right" x-data="{ cancelar: false }">
                        @if ($c->esModificable())
                            @can('consultas.gestionar')<a href="{{ route('consultas.create', ['patient_id' => $c->patient_id, 'appointment_id' => $c->id]) }}" class="btn-primary btn-sm">Atender</a>@endcan
                            @can('citas.gestionar')
                                @if ($c->estado === 'programada')<form method="POST" action="{{ route('citas.confirmar', $c) }}" class="inline">@csrf @method('PATCH')<button class="btn-secondary btn-sm">Confirmar</button></form>@endif
                                <form method="POST" action="{{ route('citas.recordatorio', $c) }}" class="inline">@csrf<button class="btn-secondary btn-sm" title="Enviar recordatorio por correo y SMS">Recordar</button></form>
                                <a href="{{ route('citas.reprogramar.edit', $c) }}" class="btn-secondary btn-sm">Reprogramar</a>
                                @if ($c->fecha_hora->isPast())<form method="POST" action="{{ route('citas.no_asistio', $c) }}" class="inline">@csrf @method('PATCH')<button class="btn-secondary btn-sm">No asistió</button></form>@endif
                                <button type="button" class="btn-danger btn-sm" @click="cancelar = !cancelar">Cancelar</button>
                                <form x-show="cancelar" x-cloak method="POST" action="{{ route('citas.cancelar', $c) }}" class="mt-2 flex justify-end gap-1">@csrf @method('PATCH')<input name="motivo" required placeholder="Motivo de cancelación" class="form-input w-52 py-1 text-xs"><button class="btn-danger btn-sm">Confirmar cancelación</button></form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-slate-500">No hay citas para este día.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<p class="mt-3 text-xs text-slate-500">Política de la clínica: las citas solo pueden cancelarse o reprogramarse hasta {{ config('clinica.horas_minimas_cancelacion') }} horas antes.</p>
@endsection
