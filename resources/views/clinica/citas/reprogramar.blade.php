@extends('layouts.app')
@section('title', 'Reprogramar cita')
@section('header', 'Reprogramar cita')

@section('content')
<form method="POST" action="{{ route('citas.reprogramar', $cita) }}" class="card max-w-2xl">
    @csrf
    <div class="card-body space-y-4">
        <div class="alert-info">{{ $cita->patient->nombre_completo }} · {{ $cita->doctor->name }} · actual: {{ $cita->fecha_hora->format('d/m/Y H:i') }} ({{ ucfirst($cita->modalidad) }})</div>
        @unless ($politica)<div class="alert-warning">Faltan menos de {{ config('clinica.horas_minimas_cancelacion') }} horas para la cita: la política de la clínica no permite reprogramarla.</div>@endunless
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="form-label" for="fecha">Nueva fecha *</label><input id="fecha" type="date" name="fecha" min="{{ today()->toDateString() }}" value="{{ old('fecha') }}" class="form-input" required></div>
            <div><label class="form-label" for="hora">Nueva hora *</label><input id="hora" type="time" name="hora" step="900" value="{{ old('hora', $cita->fecha_hora->format('H:i')) }}" class="form-input" required></div>
        </div>
        <div><label class="form-label" for="motivo">Motivo *</label><input id="motivo" name="motivo" value="{{ old('motivo') }}" class="form-input" required></div>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('citas.index', ['fecha' => $cita->fecha_hora->toDateString()]) }}" class="btn-secondary">Volver</a><button class="btn-primary">Reprogramar</button></div>
</form>
@endsection
