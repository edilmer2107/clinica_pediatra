@extends('layouts.app')
@section('title', 'Registrar ESAVI')
@section('header', 'Registrar ESAVI')

@section('content')
<form method="POST" action="{{ route('esavi.store', $inmunizacion) }}" class="card max-w-3xl">
    @csrf
    <div class="card-body space-y-4">
        <div class="alert-info">{{ $inmunizacion->patient->nombre_completo }} · {{ $inmunizacion->vaccine->nombre }} ({{ $inmunizacion->scheduleDose?->nombre_dosis }}) · Lote {{ $inmunizacion->lot->numero_lote }} · aplicada el {{ $inmunizacion->fecha_aplicacion->format('d/m/Y') }}</div>
        <div class="grid gap-4 md:grid-cols-3">
            <div><label class="form-label" for="fecha_inicio_sintomas">Inicio de síntomas *</label><input id="fecha_inicio_sintomas" type="datetime-local" name="fecha_inicio_sintomas" value="{{ old('fecha_inicio_sintomas') }}" class="form-input" required></div>
            <div><label class="form-label" for="gravedad">Gravedad *</label><select id="gravedad" name="gravedad" class="form-input" required><option value="leve">Leve</option><option value="moderado">Moderado</option><option value="grave">Grave</option></select></div>
            <div><label class="form-label" for="desenlace">Desenlace *</label><select id="desenlace" name="desenlace" class="form-input" required>@foreach (['recuperado' => 'Recuperado', 'en_seguimiento' => 'En seguimiento', 'hospitalizado' => 'Hospitalizado', 'secuelas' => 'Con secuelas', 'fallecido' => 'Fallecido'] as $k => $t)<option value="{{ $k }}">{{ $t }}</option>@endforeach</select></div>
        </div>
        <div><label class="form-label" for="sintomas">Síntomas *</label><textarea id="sintomas" name="sintomas" rows="2" class="form-input" required>{{ old('sintomas') }}</textarea></div>
        <div><label class="form-label" for="descripcion">Descripción clínica</label><textarea id="descripcion" name="descripcion" rows="3" class="form-input">{{ old('descripcion') }}</textarea></div>
        <div><label class="form-label" for="tratamiento">Tratamiento indicado</label><textarea id="tratamiento" name="tratamiento" rows="2" class="form-input">{{ old('tratamiento') }}</textarea></div>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ url()->previous() }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Registrar ESAVI</button></div>
</form>
@endsection
