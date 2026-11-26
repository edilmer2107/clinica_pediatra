@extends('layouts.app')
@section('title', 'Consulta')
@section('header', 'Consulta · '.$consulta->patient->nombre_completo)

@section('content')
@php($p = $consulta->patient)
@php($editable = $consulta->estado === 'abierta' && (auth()->id() === $consulta->doctor_id || auth()->user()->hasRole('administrador')))
<div class="card mb-4">
    <div class="card-body flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-bold">{{ $p->nombre_completo }} <span class="text-sm font-normal text-slate-500">{{ $p->historia_clinica }} · {{ $p->edad_texto }} · {{ $p->sexo === 'M' ? 'M' : 'F' }}</span></h2>
            <p class="text-sm text-slate-600">{{ $consulta->fecha->format('d/m/Y H:i') }} · {{ $consulta->doctor->name }} · {{ \App\Models\Appointment::SERVICIOS[$consulta->servicio] ?? $consulta->servicio }} · Plantilla {{ \App\Services\Clinica\PlantillaHistoriaService::PLANTILLAS[$consulta->plantilla] }}</p>
            @if ($p->alergias)<p class="text-sm font-semibold text-red-700">⚠ Alergias: {{ $p->alergias }}</p>@endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('pacientes.show', $p) }}" class="btn-secondary btn-sm">Ficha del paciente</a>
            @if ($editable)<form method="POST" action="{{ route('consultas.cerrar', $consulta) }}">@csrf @method('PATCH')<button class="btn-secondary btn-sm">Cerrar consulta</button></form>@endif
            <span class="{{ $consulta->estado === 'cerrada' ? 'badge-gray' : 'badge-green' }}">{{ ucfirst($consulta->estado) }}</span>
        </div>
    </div>
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <div class="card"><div class="card-header"><h3 class="card-title">Historia clínica</h3></div>
            <div class="card-body space-y-3 text-sm">
                <p><strong>Motivo:</strong> {{ $consulta->motivo_consulta }}</p>
                @if ($consulta->enfermedad_actual)<p><strong>Enfermedad actual:</strong> {{ $consulta->enfermedad_actual }}</p>@endif
                <div class="grid gap-2 md:grid-cols-2">@foreach ($interrogatorio as $k => [$et]) @if (! empty($consulta->interrogatorio[$k]))<div><span class="text-slate-500">{{ $et }}:</span> {{ $consulta->interrogatorio[$k] }}</div>@endif @endforeach</div>
                <p class="text-slate-600">T° {{ $consulta->temperatura ?? '—' }} °C · FC {{ $consulta->frecuencia_cardiaca ?? '—' }} · FR {{ $consulta->frecuencia_respiratoria ?? '—' }} · SatO₂ {{ $consulta->saturacion_o2 ?? '—' }}% · PA {{ $consulta->presion_arterial ?? '—' }}</p>
                <div class="grid gap-1 md:grid-cols-2">@foreach ($examen as $k => $et) @if (! empty($consulta->examen_fisico[$k]))<div><span class="text-slate-500">{{ $et }}:</span> {{ $consulta->examen_fisico[$k] }}</div>@endif @endforeach</div>
                <div><strong>Diagnósticos:</strong> @foreach ($consulta->diagnoses as $d)<span class="badge-blue mr-1">{{ $d->cie10->codigo }} {{ $d->cie10->descripcion }} ({{ $d->tipo }}{{ $d->principal ? ', principal' : '' }})</span>@endforeach</div>
                @if ($consulta->plan_tratamiento)<p><strong>Plan:</strong> {{ $consulta->plan_tratamiento }}</p>@endif
                @if ($consulta->indicaciones)<p><strong>Indicaciones:</strong> {{ $consulta->indicaciones }}</p>@endif
            </div>
        </div>

        @can('recetas.emitir')@if ($editable)
            @include('clinica.recetas._form')
        @endif @endcan

        <div class="card"><div class="card-header"><h3 class="card-title">Recetas de esta consulta</h3></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($consulta->prescriptions as $r)
                    <li class="px-5 py-3"><a href="{{ route('recetas.show', $r) }}" class="font-medium text-brand-700 hover:underline">{{ $r->codigo }}</a> <span class="badge-gray">{{ ucfirst($r->estado) }}</span>
                        <div class="text-xs text-slate-600">@foreach ($r->items as $i){{ $i->medication->nombre }} {{ rtrim(rtrim(number_format($i->dosis_por_toma_mg, 1), '0'), '.') }} mg c/{{ $i->frecuencia_horas }}h × {{ $i->duracion_dias }}d @if ($i->alertas)<span class="text-red-700">⚠</span>@endif @if (! $loop->last)· @endif @endforeach</div></li>
                @empty
                    <li class="px-5 py-4 text-slate-500">Sin recetas.</li>
                @endforelse
            </ul>
        </div>

        <div class="card"><div class="card-header"><h3 class="card-title">Órdenes de laboratorio e imágenes</h3></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($consulta->labOrders as $o)
                    <li class="px-5 py-3">
                        <div class="flex justify-between"><span><strong>{{ $o->codigo }}</strong> · {{ ucfirst($o->prioridad) }}</span><span class="{{ $o->estado === 'completada' ? 'badge-green' : 'badge-yellow' }}">{{ ucfirst($o->estado) }}</span></div>
                        <div class="text-xs">@foreach ($o->items as $it)<span class="{{ $it->es_anormal ? 'font-semibold text-red-700' : '' }}">{{ $it->labTest->nombre }}@if ($it->resultado_at): {{ $it->valor ?? $it->resultado_texto }} {{ $it->labTest->unidad }}{{ $it->es_anormal ? ' (ANORMAL)' : '' }}@endif</span>@if (! $loop->last) · @endif @endforeach</div>
                        @can('laboratorio.resultados')<a href="{{ route('laboratorio.show', $o) }}" class="text-xs text-brand-700 hover:underline">Ver / registrar resultados</a>@endcan
                    </li>
                @empty
                    <li class="px-5 py-4 text-slate-500">Sin órdenes.</li>
                @endforelse
            </ul>
            @can('laboratorio.solicitar')@if ($editable)
            <form method="POST" action="{{ route('laboratorio.store', $consulta) }}" class="space-y-3 border-t border-slate-200 p-5">
                @csrf
                <div class="grid gap-1 text-sm md:grid-cols-2">@foreach ($examenes as $e)<label class="flex items-center gap-2"><input type="checkbox" name="examenes[]" value="{{ $e->id }}"> {{ $e->nombre }} <span class="text-xs text-slate-500">({{ $e->tipo }})</span></label>@endforeach</div>
                <div class="flex flex-wrap gap-2"><select name="prioridad" class="form-input w-36"><option value="rutina">Rutina</option><option value="urgente">Urgente</option></select><input name="indicaciones" placeholder="Indicaciones" class="form-input flex-1"><button class="btn-primary">Generar orden</button></div>
            </form>
            @endif @endcan
        </div>
    </div>

    <div class="space-y-4">
        @can('mediciones.registrar')
        <form method="POST" action="{{ route('pacientes.mediciones.store', $p) }}" class="card">
            @csrf
            <input type="hidden" name="consultation_id" value="{{ $consulta->id }}"><input type="hidden" name="volver" value="consulta"><input type="hidden" name="fecha" value="{{ today()->toDateString() }}">
            <div class="card-header"><h3 class="card-title">Antropometría (OMS)</h3></div>
            <div class="card-body space-y-2">
                @if ($ultimaMedicion)<p class="text-xs text-slate-500">Última: {{ $ultimaMedicion->fecha->format('d/m/Y') }} · {{ $ultimaMedicion->peso_kg }} kg · {{ $ultimaMedicion->talla_cm }} cm @if ($ultimaMedicion->z_peso_edad !== null)· Z P/E {{ $ultimaMedicion->z_peso_edad }}@endif</p>@endif
                <input type="number" step="0.01" name="peso_kg" placeholder="Peso (kg)" class="form-input" required aria-label="Peso">
                <input type="number" step="0.1" name="talla_cm" placeholder="Talla (cm)" class="form-input" required aria-label="Talla">
                <input type="number" step="0.1" name="perimetro_cefalico_cm" placeholder="Perímetro cefálico (cm)" class="form-input" aria-label="Perímetro cefálico">
                <button class="btn-primary w-full">Calcular percentiles</button>
            </div>
        </form>
        @endcan
        @if ($editable && $procedimientos->isNotEmpty())
        <form method="POST" action="{{ route('consultas.procedimiento', $consulta) }}" class="card">
            @csrf
            <div class="card-header"><h3 class="card-title">Procedimientos</h3></div>
            <div class="card-body space-y-2">
                <select name="service_id" class="form-input" aria-label="Procedimiento">@foreach ($procedimientos as $s)<option value="{{ $s->id }}">{{ $s->nombre }} · S/ {{ number_format($s->precio, 2) }}</option>@endforeach</select>
                <input type="number" name="cantidad" value="1" min="1" max="10" class="form-input" aria-label="Cantidad">
                <button class="btn-secondary w-full">Cargar a la cuenta</button>
            </div>
        </form>
        @endif
        <div class="card"><div class="card-header"><h3 class="card-title">Tutores</h3></div>
            <ul class="divide-y divide-slate-100 text-xs">@foreach ($p->tutors as $t)<li class="px-5 py-2">{{ $t->nombre_completo }} ({{ $t->pivot->parentesco }}) · {{ $t->telefono }}</li>@endforeach</ul></div>
    </div>
</div>
@endsection
