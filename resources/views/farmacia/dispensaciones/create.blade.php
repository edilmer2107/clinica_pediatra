@extends('layouts.app')
@section('title', 'Dispensar '.$receta->codigo)
@section('header', 'Farmacia · Dispensar receta '.$receta->codigo)

@section('content')
<div class="mb-4 grid gap-4 md:grid-cols-3">
    <div class="stat"><div class="stat-label">Paciente</div><div class="mt-1 font-semibold">{{ $receta->patient->nombre_completo }}</div><div class="text-xs text-slate-500">{{ $receta->patient->historia_clinica }} · {{ $receta->patient->edad_texto }} · {{ $receta->peso_kg }} kg</div></div>
    <div class="stat"><div class="stat-label">Médico prescriptor</div><div class="mt-1 font-semibold">{{ $receta->doctor->name }}</div><div class="text-xs text-slate-500">{{ $receta->doctor->colegiatura }}</div></div>
    <div class="stat"><div class="stat-label">Alergias</div><div class="mt-1 text-sm {{ $receta->patient->alergias ? 'font-semibold text-red-700' : '' }}">{{ $receta->patient->alergias ?: 'Ninguna registrada' }}</div></div>
</div>

<form method="POST" action="{{ route('dispensaciones.store', $receta) }}" class="card">
    @csrf
    <div class="card-header"><h2 class="card-title">Validación farmacéutica</h2></div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Medicamento prescrito</th><th>Indicación</th><th>Pendiente</th><th>Lote (sugerencia FEFO primero)</th><th>Cantidad a entregar</th></tr></thead>
            <tbody>
            @foreach ($receta->items as $i => $item)
                <tr>
                    <td>
                        <div class="font-medium">{{ $item->medication->nombre_completo }}</div>
                        @if ($item->medication->es_controlado)<span class="badge-red">Controlado: exige tutor identificado</span>@endif
                        <input type="hidden" name="lineas[{{ $i }}][prescription_item_id]" value="{{ $item->id }}">
                        <input type="hidden" name="lineas[{{ $i }}][medication_id]" value="{{ $item->medication_id }}">
                    </td>
                    <td class="text-xs">{{ rtrim(rtrim(number_format($item->dosis_por_toma_mg, 2), '0'), '.') }} mg c/{{ $item->frecuencia_horas }} h × {{ $item->duracion_dias }} días ({{ $item->via }})</td>
                    <td>{{ $item->cantidadPendiente() }} {{ $item->medication->unidad }}</td>
                    <td>
                        @if ($item->cantidadPendiente() > 0)
                            <select name="lineas[{{ $i }}][lot_id]" class="form-input">
                                @forelse ($lotes[$item->id] as $k => $lote)
                                    <option value="{{ $lote->id }}" @selected(old("lineas.$i.lot_id") == $lote->id)>{{ $lote->numero_lote }} · vence {{ $lote->fecha_vencimiento->format('d/m/Y') }} · stock {{ $lote->cantidad_actual }} · {{ $lote->ubicacion }}{{ $k === 0 ? ' (FEFO)' : '' }}</option>
                                @empty
                                    <option value="">Sin lotes vigentes</option>
                                @endforelse
                            </select>
                        @else
                            <span class="badge-green">Completado</span>
                        @endif
                    </td>
                    <td>
                        @if ($item->cantidadPendiente() > 0)
                            <input type="number" min="0" max="{{ $item->cantidadPendiente() }}" name="lineas[{{ $i }}][cantidad]" value="{{ old("lineas.$i.cantidad", $item->cantidadPendiente()) }}" class="form-input w-24">
                        @else
                            <input type="hidden" name="lineas[{{ $i }}][cantidad]" value="0">—
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-body grid gap-4 border-t border-slate-200 md:grid-cols-2">
        <div>
            <label class="form-label" for="tutor_id">Tutor que retira</label>
            <select id="tutor_id" name="tutor_id" class="form-input">
                <option value="">— No identificado —</option>
                @foreach ($receta->patient->tutors as $t)
                    <option value="{{ $t->id }}" @selected(old('tutor_id') == $t->id) @disabled(! $t->pivot->puede_retirar_medicamentos)>{{ $t->nombre_completo }} · DNI {{ $t->dni }} · {{ $t->pivot->parentesco }}{{ $t->pivot->puede_retirar_medicamentos ? '' : ' (no autorizado)' }}</option>
                @endforeach
            </select>
            <p class="form-help">Obligatorio para medicamentos controlados.</p>
        </div>
        <div><label class="form-label" for="observaciones">Observaciones</label><textarea id="observaciones" name="observaciones" rows="2" class="form-input">{{ old('observaciones') }}</textarea></div>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
        <a href="{{ route('dispensaciones.index') }}" class="btn-secondary">Cancelar</a>
        <button class="btn-primary">Registrar dispensación</button>
    </div>
</form>
@endsection
