@extends('layouts.portal')
@section('title', 'Resultados '.$orden->codigo)

@section('content')
<a href="{{ route('portal.hijo', $orden->patient) }}" class="text-sm text-brand-700 hover:underline">← Volver</a>
<div class="card mt-2">
    <div class="card-header"><h1 class="card-title">Resultados {{ $orden->codigo }} · {{ $orden->patient->nombre_completo }}</h1><a href="{{ route('portal.resultado.pdf', $orden) }}" class="btn-secondary btn-sm">Descargar PDF</a></div>
    <table class="table">
        <thead><tr><th>Examen</th><th>Resultado</th><th>Referencia</th><th>Archivo</th></tr></thead>
        <tbody>@foreach ($orden->items as $it)
            <tr class="{{ $it->es_anormal ? 'bg-red-50' : '' }}"><td>{{ $it->labTest->nombre }}</td><td class="{{ $it->es_anormal ? 'font-semibold text-red-700' : '' }}">{{ $it->valor !== null ? (float) $it->valor.' '.$it->labTest->unidad : '' }} {{ $it->resultado_texto }} {{ $it->es_anormal ? '(fuera de rango)' : '' }}</td>
                <td class="text-xs">@if ($it->ref_min !== null){{ (float) $it->ref_min }} – {{ (float) $it->ref_max }} {{ $it->labTest->unidad }}@endif</td>
                <td>@if ($it->archivo_path)<a href="{{ route('portal.archivo', $it) }}" class="text-brand-700 hover:underline">{{ $it->archivo_nombre }}</a>@endif</td></tr>
        @endforeach</tbody>
    </table>
    <p class="px-5 py-3 text-xs text-slate-500">Consulte a su médico tratante ({{ $orden->doctor->name }}) para la interpretación de los resultados.</p>
</div>
@endsection
