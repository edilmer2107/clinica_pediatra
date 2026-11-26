@extends('layouts.app')
@section('title', 'Aplicar vacuna')
@section('header', 'Aplicar vacuna · '.$paciente->nombre_completo)

@section('content')
<form method="POST" action="{{ route('vacunacion.store', [$paciente, $dosis]) }}" class="card max-w-2xl">
    @csrf
    <div class="card-body space-y-4">
        <div class="alert-info"><strong>{{ $dosis->vaccine->nombre }}</strong> — {{ $dosis->nombre_dosis }} · Vía {{ $dosis->vaccine->via }} · Previene: {{ $dosis->vaccine->enfermedades }}</div>
        <div><label class="form-label" for="lot_id">Lote (FEFO: el primero vence antes) *</label>
            <select id="lot_id" name="lot_id" class="form-input" required>
                @forelse ($lotes as $k => $l)
                    <option value="{{ $l->id }}">{{ $l->numero_lote }} · vence {{ $l->fecha_vencimiento->format('d/m/Y') }} · stock {{ $l->cantidad_actual }}{{ $k === 0 ? ' (sugerido FEFO)' : '' }}</option>
                @empty
                    <option value="">Sin stock disponible de esta vacuna</option>
                @endforelse
            </select></div>
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="form-label" for="fecha_aplicacion">Fecha de aplicación *</label><input id="fecha_aplicacion" type="date" name="fecha_aplicacion" value="{{ old('fecha_aplicacion', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-input" required></div>
            <div><label class="form-label" for="sitio_aplicacion">Sitio de aplicación *</label><select id="sitio_aplicacion" name="sitio_aplicacion" class="form-input" required>@foreach (['Muslo derecho', 'Muslo izquierdo', 'Brazo derecho', 'Brazo izquierdo', 'Deltoides', 'Oral'] as $s)<option>{{ $s }}</option>@endforeach</select></div>
        </div>
        <div><label class="form-label" for="observaciones">Observaciones</label><input id="observaciones" name="observaciones" value="{{ old('observaciones') }}" class="form-input"></div>
        <p class="form-help">Al registrar, se descuenta automáticamente 1 dosis del lote seleccionado en el inventario de farmacia.</p>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('pacientes.show', [$paciente, 'tab' => 'vacunas']) }}" class="btn-secondary">Cancelar</a><button class="btn-primary" @disabled($lotes->isEmpty())>Registrar aplicación</button></div>
</form>
@endsection
