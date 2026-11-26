@extends('layouts.app')
@section('title', 'Registrar error')
@section('header', 'Farmacia · Registrar error de medicación')

@section('content')
<form method="POST" action="{{ route('errores.store') }}" enctype="multipart/form-data" class="card max-w-3xl">
    @csrf
    <div class="card-body grid gap-4 md:grid-cols-2">
        <div><label class="form-label" for="tipo">Tipo *</label><select id="tipo" name="tipo" class="form-input" required><option value="dispensacion" @selected(old('tipo') === 'dispensacion')>Dispensación</option><option value="prescripcion" @selected(old('tipo') === 'prescripcion')>Prescripción</option></select></div>
        <div><label class="form-label" for="categoria">Categoría *</label><select id="categoria" name="categoria" class="form-input" required>@foreach ($categorias as $k => $v)<option value="{{ $k }}" @selected(old('categoria') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label class="form-label" for="prescription_id">Receta relacionada</label><select id="prescription_id" name="prescription_id" class="form-input"><option value="">—</option>@foreach ($recetas as $r)<option value="{{ $r->id }}" @selected(old('prescription_id') == $r->id)>{{ $r->codigo }}</option>@endforeach</select></div>
        <div><label class="form-label" for="dispensation_id">Dispensación relacionada</label><select id="dispensation_id" name="dispensation_id" class="form-input"><option value="">—</option>@foreach ($dispensaciones as $d)<option value="{{ $d->id }}" @selected(old('dispensation_id') == $d->id)>{{ $d->codigo }}</option>@endforeach</select></div>
        <div><label class="form-label" for="fecha">Fecha *</label><input id="fecha" type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-input" required></div>
        <div><label class="form-label" for="gravedad">Gravedad *</label><select id="gravedad" name="gravedad" class="form-input" required>@foreach (['sin_dano' => 'Sin daño', 'leve' => 'Leve', 'moderado' => 'Moderado', 'grave' => 'Grave'] as $k => $v)<option value="{{ $k }}" @selected(old('gravedad') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="md:col-span-2"><label class="form-label" for="descripcion">Descripción del error *</label><textarea id="descripcion" name="descripcion" rows="3" class="form-input" required>{{ old('descripcion') }}</textarea></div>
        <div class="md:col-span-2"><label class="form-label" for="medida_correctiva">Medida correctiva *</label><textarea id="medida_correctiva" name="medida_correctiva" rows="2" class="form-input" required>{{ old('medida_correctiva') }}</textarea></div>
        <div class="md:col-span-2"><label class="form-label" for="evidencia">Evidencia documental (PDF, JPG o PNG, máx. 5 MB)</label><input id="evidencia" type="file" name="evidencia" accept=".pdf,.jpg,.jpeg,.png" class="form-input"></div>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('errores.index') }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Registrar</button></div>
</form>
@endsection
