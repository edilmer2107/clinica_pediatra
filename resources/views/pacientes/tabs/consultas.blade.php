<div class="grid gap-4 lg:grid-cols-2">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Consultas recientes</h3></div>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($paciente->consultations as $c)
                <li class="px-5 py-3">
                    <div class="flex justify-between">
                        @if (Route::has('consultas.show'))<a href="{{ route('consultas.show', $c) }}" class="font-medium text-brand-700 hover:underline">{{ $c->fecha->format('d/m/Y H:i') }}</a>@else<span>{{ $c->fecha->format('d/m/Y H:i') }}</span>@endif
                        <span class="text-xs text-slate-500">{{ $c->doctor->name }}</span>
                    </div>
                    <div class="text-xs">{{ $c->motivo_consulta }}</div>
                    <div class="text-xs text-slate-500">@foreach ($c->diagnoses as $d){{ $d->cie10->codigo }} {{ $d->cie10->descripcion }}@if(!$loop->last); @endif @endforeach</div>
                </li>
            @empty
                <li class="px-5 py-4 text-slate-500">Sin consultas registradas.</li>
            @endforelse
        </ul>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Citas</h3></div>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($paciente->appointments as $a)
                <li class="flex justify-between px-5 py-3"><span>{{ $a->fecha_hora->format('d/m/Y H:i') }} · {{ $a->doctor->name }} · {{ ucfirst($a->modalidad) }}</span><span class="badge-gray">{{ \App\Models\Appointment::ESTADOS[$a->estado] }}</span></li>
            @empty
                <li class="px-5 py-4 text-slate-500">Sin citas.</li>
            @endforelse
        </ul>
    </div>
</div>
