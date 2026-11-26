@extends('layouts.app')
@section('title', 'Receta '.$receta->codigo)
@section('header', 'Receta '.$receta->codigo)

@section('content')
@php($puedeEditar = $receta->estado === 'emitida' && (auth()->id() === $receta->doctor_id || auth()->user()->hasRole('administrador')) && auth()->user()->can('recetas.emitir'))
<div class="card max-w-5xl">
    <div class="card-header">
        <div><h2 class="card-title">Receta electrónica {{ $receta->codigo }}</h2><p class="text-xs text-slate-500">{{ $receta->created_at->format('d/m/Y H:i') }} · Estado: {{ ucfirst($receta->estado) }}</p></div>
        <div class="flex gap-2 no-print">
            <a href="{{ route('recetas.pdf', $receta) }}" class="btn-secondary btn-sm">Descargar PDF</a>
            <button onclick="window.print()" class="btn-secondary btn-sm">Imprimir</button>
            @if ($receta->consultation)<a href="{{ route('consultas.show', $receta->consultation) }}" class="btn-secondary btn-sm">Ir a la consulta</a>@endif
        </div>
    </div>
    <dl class="card-body grid grid-cols-2 gap-2 text-sm md:grid-cols-4">
        <dt class="text-slate-500">Paciente</dt><dd>{{ $receta->patient->nombre_completo }} ({{ $receta->patient->historia_clinica }})</dd>
        <dt class="text-slate-500">Edad / peso / talla</dt><dd>{{ $receta->patient->edad_texto }} · {{ $receta->peso_kg }} kg · {{ $receta->talla_cm ?? '—' }} cm @if ($receta->superficie_corporal)· SC {{ $receta->superficie_corporal }} m²@endif</dd>
        <dt class="text-slate-500">Médico</dt><dd>{{ $receta->doctor->name }} · {{ $receta->doctor->colegiatura }}</dd>
        <dt class="text-slate-500">Diagnóstico</dt><dd>{{ $receta->consultation?->diagnoses->pluck('cie10.codigo')->implode(', ') ?: '—' }}</dd>
    </dl>
    <table class="table border-t border-slate-200">
        <thead><tr><th>Medicamento</th><th>Dosis indicada</th><th>Dosis/toma</th><th>Frecuencia</th><th>Duración</th><th>Vía</th><th>Cantidad</th><th>Dispensado</th></tr></thead>
        <tbody>
        @foreach ($receta->items as $i)
            <tr>
                <td><div class="font-medium">{{ $i->medication->nombre_completo }}</div>@if ($i->indicaciones)<div class="text-xs text-slate-500">{{ $i->indicaciones }}</div>@endif
                    @if ($i->alertas)<div class="text-xs font-semibold text-red-700">⚠ {{ $i->alertas }}</div>@endif
                    @if ($i->justificacion)<div class="text-xs text-amber-700">Justificación: {{ $i->justificacion }}</div>@endif</td>
                <td>{{ (float) $i->dosis_indicada }} {{ $i->metodo_calculo === 'sc' ? 'mg/m²/día' : 'mg/kg/día' }}<div class="text-xs text-slate-500">{{ (float) $i->dosis_diaria_mg }} mg/día</div></td>
                <td>{{ (float) $i->dosis_por_toma_mg }} mg @if ($i->volumen_por_toma_ml)<div class="text-xs text-slate-500">{{ $i->volumen_por_toma_ml }} mL</div>@endif</td>
                <td>c/{{ $i->frecuencia_horas }} h</td><td>{{ $i->duracion_dias }} días</td><td>{{ $i->via }}</td><td>{{ $i->cantidad }} {{ $i->medication->unidad }}</td><td>{{ $i->cantidad_dispensada }}</td>
            </tr>
            @if ($puedeEditar)
            <tr class="no-print"><td colspan="8">
                <form method="POST" action="{{ route('recetas.items.update', $i) }}" class="flex flex-wrap items-center gap-2 text-xs">
                    @csrf @method('PUT') <span class="text-slate-500">Modificar posología:</span>
                    <input type="number" step="any" name="dosis_indicada" value="{{ (float) $i->dosis_indicada }}" class="form-input w-24 py-1" aria-label="Dosis">
                    <select name="frecuencia_horas" class="form-input w-24 py-1" aria-label="Frecuencia">@foreach ([4, 6, 8, 12, 24] as $h)<option value="{{ $h }}" @selected($i->frecuencia_horas == $h)>c/{{ $h }} h</option>@endforeach</select>
                    <input type="number" name="duracion_dias" value="{{ $i->duracion_dias }}" class="form-input w-20 py-1" aria-label="Días">
                    <input name="justificacion" placeholder="Justificación (si aplica)" class="form-input w-48 py-1">
                    <input name="motivo" placeholder="Motivo del cambio *" required class="form-input w-48 py-1">
                    <button class="btn-secondary btn-sm">Guardar</button>
                </form>
            </td></tr>
            @endif
        @endforeach
        </tbody>
    </table>
    @if ($receta->observaciones)<p class="px-5 py-3 text-sm whitespace-pre-line"><strong>Observaciones:</strong> {{ $receta->observaciones }}</p>@endif
    @if ($puedeEditar)
        <form method="POST" action="{{ route('recetas.anular', $receta) }}" class="no-print flex justify-end gap-2 border-t border-slate-200 px-5 py-3" onsubmit="return confirm('¿Anular la receta?')">
            @csrf @method('PATCH')<input name="motivo" required placeholder="Motivo de anulación" class="form-input w-72"><button class="btn-danger">Anular receta</button>
        </form>
    @endif
</div>
@endsection
