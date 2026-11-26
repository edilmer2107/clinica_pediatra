@extends('layouts.portal')
@section('title', $paciente->nombre_completo)

@section('content')
<div class="mb-4 flex items-center justify-between"><div><a href="{{ route('portal.index') }}" class="text-sm text-brand-700 hover:underline">← Mis hijos</a><h1 class="text-xl font-bold">{{ $paciente->nombre_completo }}</h1><p class="text-sm text-slate-500">{{ $paciente->edad_texto }} · {{ $paciente->historia_clinica }}</p></div></div>

<div class="grid gap-4 lg:grid-cols-2">
    @if ($enRangoOms)
        @foreach ($graficos as $clave => $g)
            <div class="card"><div class="card-header"><h2 class="card-title">{{ $g['titulo'] }}</h2></div><div class="card-body"><canvas id="pg_{{ $clave }}"></canvas></div></div>
        @endforeach
    @endif
    <div class="card lg:col-span-2"><div class="card-header"><h2 class="card-title">Calendario de vacunas</h2></div>
        <div class="overflow-x-auto"><table class="table">
            <thead><tr><th>Vacuna</th><th>Dosis</th><th>Fecha</th><th>Estado</th></tr></thead>
            <tbody>@php($cls = ['aplicada' => 'badge-green', 'vencida' => 'badge-red', 'pendiente' => 'badge-yellow', 'proxima' => 'badge-blue', 'futura' => 'badge-gray'])
            @foreach ($calendario->whereIn('estado', ['vencida', 'pendiente', 'proxima', 'aplicada']) as $c)
                <tr><td>{{ $c['dosis']->vaccine->nombre }}</td><td class="text-xs">{{ $c['dosis']->nombre_dosis }}</td><td>{{ $c['aplicacion'] ? $c['aplicacion']->fecha_aplicacion->format('d/m/Y') : $c['fecha_programada']->format('d/m/Y') }}</td>
                    <td><span class="{{ $cls[$c['estado']] }}">{{ ['aplicada' => 'Aplicada', 'vencida' => 'Vencida', 'pendiente' => 'Pendiente', 'proxima' => 'Próxima'][$c['estado']] }}</span></td></tr>
            @endforeach</tbody></table></div></div>

    <div class="card"><div class="card-header"><h2 class="card-title">Recetas</h2></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($paciente->prescriptions as $r)
            <li class="flex items-center justify-between px-5 py-3"><div><strong>{{ $r->codigo }}</strong> · {{ $r->created_at->format('d/m/Y') }}<div class="text-xs text-slate-500">{{ $r->items->pluck('medication.nombre')->implode(', ') }}</div>@if ($r->lista_para_retiro_at && $r->estado !== 'dispensada')<span class="badge-green">Lista para retiro</span>@endif</div>
                <a href="{{ route('portal.receta', $r) }}" class="btn-secondary btn-sm">Descargar PDF</a></li>
        @empty<li class="px-5 py-4 text-slate-500">Sin recetas.</li>@endforelse</ul></div>

    <div class="card"><div class="card-header"><h2 class="card-title">Resultados de laboratorio e imágenes</h2></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($paciente->labOrders as $o)
            <li class="flex items-center justify-between px-5 py-3"><div><strong>{{ $o->codigo }}</strong> · {{ $o->created_at->format('d/m/Y') }}<div class="text-xs text-slate-500">{{ $o->items->pluck('labTest.nombre')->implode(', ') }}</div></div>
                @if ($o->estado === 'completada')<div class="flex gap-1"><a href="{{ route('portal.resultado', $o) }}" class="btn-secondary btn-sm">Ver</a><a href="{{ route('portal.resultado.pdf', $o) }}" class="btn-secondary btn-sm">PDF</a></div>@else<span class="badge-yellow">En proceso</span>@endif</li>
        @empty<li class="px-5 py-4 text-slate-500">Sin exámenes.</li>@endforelse</ul></div>

    <div class="card lg:col-span-2"><div class="card-header"><h2 class="card-title">Historial de consultas</h2></div>
        <ul class="divide-y divide-slate-100 text-sm">@forelse ($paciente->consultations as $c)
            <li class="px-5 py-3"><strong>{{ $c->fecha->format('d/m/Y') }}</strong> · {{ $c->doctor->name }} · {{ $c->motivo_consulta }}<div class="text-xs text-slate-500">{{ $c->diagnoses->map(fn ($d) => $d->cie10->descripcion)->implode('; ') }}</div>@if ($c->indicaciones)<div class="text-xs">Indicaciones: {{ $c->indicaciones }}</div>@endif</li>
        @empty<li class="px-5 py-4 text-slate-500">Sin consultas.</li>@endforelse</ul></div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const graficos = @json($graficos);
    const col = { P3: '#e4572e', P50: '#17a589', P97: '#e4572e' };
    for (const [k, g] of Object.entries(graficos)) {
        const c = document.getElementById('pg_' + k); if (!c) continue;
        const ds = Object.entries(g.curvas).map(([n, d]) => ({ label: n, data: d, borderColor: col[n], pointRadius: 0, borderWidth: n === 'P50' ? 2 : 1, borderDash: n === 'P50' ? [] : [4, 3] }));
        ds.push({ label: 'Mi hijo(a)', data: g.puntos, borderColor: '#1f6fc0', backgroundColor: '#1f6fc0', pointRadius: 4 });
        new window.Chart(c, { type: 'line', data: { datasets: ds }, options: { parsing: false, aspectRatio: 1.6, scales: { x: { type: 'linear', title: { display: true, text: 'Edad (meses)' } }, y: { title: { display: true, text: g.etiqueta } } } } });
    }
});
</script>
@endpush
@endsection
