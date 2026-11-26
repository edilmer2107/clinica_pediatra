@extends('layouts.portal')
@section('title', 'Mis hijos')

@section('content')
<h1 class="mb-1 text-xl font-bold">Hola, {{ $tutor->nombres }}</h1>
<p class="mb-5 text-sm text-slate-600">Aquí puede ver la información de todos los niños vinculados a su cuenta.</p>
<div class="grid gap-4 md:grid-cols-2">
    @foreach ($hijos as $h)
        @php($r = $resumen[$h->id])
        @php($vencidas = $r['calendario']->where('estado', 'vencida')->count())
        @php($pendientes = $r['calendario']->whereIn('estado', ['pendiente', 'proxima'])->count())
        <a href="{{ route('portal.hijo', $h) }}" class="card block transition hover:shadow-md">
            <div class="card-body">
                <div class="flex items-start justify-between"><div><h2 class="text-lg font-semibold">{{ $h->nombre_completo }}</h2><p class="text-sm text-slate-500">{{ $h->edad_texto }} · {{ $h->historia_clinica }}</p></div><span class="badge-blue">{{ $h->pivot->parentesco }}</span></div>
                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <dt class="text-slate-500">Próxima cita</dt><dd>{{ $r['proxima_cita'] ? $r['proxima_cita']->fecha_hora->format('d/m/Y H:i').' ('.$r['proxima_cita']->modalidad.')' : 'Sin citas' }}</dd>
                    <dt class="text-slate-500">Último control</dt><dd>{{ $r['ultima'] ? $r['ultima']->fecha->format('d/m/Y').' · '.$r['ultima']->peso_kg.' kg · '.$r['ultima']->talla_cm.' cm' : '—' }}</dd>
                    <dt class="text-slate-500">Vacunas</dt><dd>@if ($vencidas)<span class="badge-red">{{ $vencidas }} vencida(s)</span>@endif @if ($pendientes)<span class="badge-yellow">{{ $pendientes }} pendiente(s)/próxima(s)</span>@endif @if (! $vencidas && ! $pendientes)<span class="badge-green">Al día</span>@endif</dd>
                </dl>
            </div>
        </a>
    @endforeach
</div>
@endsection
