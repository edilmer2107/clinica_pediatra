@extends('layouts.app')
@section('title', $reporte->titulo())
@section('header', 'Reporte · '.$reporte->titulo())

@section('content')
<div class="card no-print mb-4">
    <form method="GET" class="card-body flex flex-wrap items-end gap-3">
        @foreach ($reporte->filtros() as $nombre => $def)
            <div>
                <label class="form-label" for="f_{{ $nombre }}">{{ $def[1] }}</label>
                @if ($def[0] === 'select')
                    <select id="f_{{ $nombre }}" name="{{ $nombre }}" class="form-input w-52">
                        <option value="">Todos</option>
                        @foreach ($def[2] as $valor => $texto)
                            <option value="{{ $valor }}" @selected((string) ($filtros[$nombre] ?? '') === (string) $valor)>{{ $texto }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="f_{{ $nombre }}" type="{{ $def[0] }}" name="{{ $nombre }}" value="{{ $filtros[$nombre] ?? '' }}" class="form-input w-44">
                @endif
            </div>
        @endforeach
        <button class="btn-primary">Aplicar filtros</button>
        <div class="ml-auto flex gap-2">
            <button type="button" onclick="window.print()" class="btn-secondary">Imprimir</button>
            <a href="{{ request()->fullUrlWithQuery(['formato' => 'pdf']) }}" class="btn-secondary">PDF</a>
            <a href="{{ request()->fullUrlWithQuery(['formato' => 'excel']) }}" class="btn-secondary">Excel</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h2 class="card-title">{{ $reporte->titulo() }}</h2>
            <p class="text-xs text-slate-500">{{ $reporte->descripcion() }} · Período {{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}</p>
        </div>
    </div>
    @if ($resumen)
        <div class="grid grid-cols-2 gap-3 border-b border-slate-200 p-4 md:grid-cols-4">
            @foreach ($resumen as $etiqueta => $valor)
                <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-500">{{ $etiqueta }}</div><div class="text-lg font-semibold">{{ $valor }}</div></div>
            @endforeach
        </div>
    @endif
    @if ($grafico)
        <div class="border-b border-slate-200 p-4"><canvas id="graficoReporte" height="90"></canvas></div>
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const g = @json($grafico);
                const colores = ['#1f6fc0', '#e4572e', '#17a589', '#f3a712', '#8e44ad', '#5d6d7e'];
                new window.Chart(document.getElementById('graficoReporte'), {
                    type: g.tipo || 'bar',
                    data: { labels: g.labels, datasets: Object.entries(g.series).map(([label, data], i) => ({ label, data, backgroundColor: colores[i % colores.length], borderColor: colores[i % colores.length] })) },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                });
            });
        </script>
        @endpush
    @endif
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr>@foreach ($reporte->columnas() as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse ($filas as $fila)
                <tr>@foreach ($fila as $valor)<td>{{ $valor }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($reporte->columnas()) }}" class="py-6 text-center text-slate-500">Sin datos para los filtros seleccionados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
