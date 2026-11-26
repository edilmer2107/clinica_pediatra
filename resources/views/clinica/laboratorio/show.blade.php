@extends('layouts.app')
@section('title', 'Orden '.$orden->codigo)
@section('header', 'Orden '.$orden->codigo)

@section('content')
<div class="alert-info mb-4">{{ $orden->patient->nombre_completo }} ({{ $orden->patient->historia_clinica }}) · solicitada por {{ $orden->doctor->name }} el {{ $orden->created_at->format('d/m/Y H:i') }} · {{ ucfirst($orden->prioridad) }} @if ($orden->indicaciones)· {{ $orden->indicaciones }}@endif</div>
<form method="POST" action="{{ route('laboratorio.resultados', $orden) }}" enctype="multipart/form-data" class="card">
    @csrf
    <table class="table">
        <thead><tr><th>Examen</th><th>Valor</th><th>Rango de referencia</th><th>Resultado / informe</th><th>Archivo (PDF/JPG/PNG)</th><th>Estado</th></tr></thead>
        <tbody>
        @foreach ($orden->items as $it)
            <tr class="{{ $it->es_anormal ? 'bg-red-50' : '' }}">
                <td class="font-medium">{{ $it->labTest->nombre }}<div class="text-xs text-slate-500">{{ ucfirst($it->labTest->tipo) }}</div></td>
                <td>@if ($it->labTest->tipo === 'laboratorio' && $it->ref_min !== null)<input type="number" step="any" name="resultados[{{ $it->id }}][valor]" value="{{ $it->valor }}" class="form-input w-28 {{ $it->es_anormal ? 'border-red-500 font-semibold text-red-700' : '' }}" aria-label="Valor {{ $it->labTest->nombre }}"> <span class="text-xs">{{ $it->labTest->unidad }}</span>@else — @endif</td>
                <td class="text-xs">@if ($it->ref_min !== null){{ (float) $it->ref_min }} – {{ (float) $it->ref_max }} {{ $it->labTest->unidad }}@else — @endif</td>
                <td><textarea name="resultados[{{ $it->id }}][resultado_texto]" rows="2" class="form-input" aria-label="Informe {{ $it->labTest->nombre }}">{{ $it->resultado_texto }}</textarea>
                    @if ($it->ref_min === null)<label class="mt-1 flex items-center gap-1 text-xs"><input type="checkbox" name="resultados[{{ $it->id }}][anormal]" value="1" @checked($it->es_anormal)> Hallazgo anormal</label>@endif</td>
                <td><input type="file" name="resultados[{{ $it->id }}][archivo]" accept=".pdf,.jpg,.jpeg,.png" class="text-xs" aria-label="Archivo {{ $it->labTest->nombre }}">
                    @if ($it->archivo_path)<a href="{{ route('laboratorio.archivo', $it) }}" class="mt-1 block text-xs text-brand-700 hover:underline">{{ $it->archivo_nombre }}</a>@endif</td>
                <td>@if ($it->resultado_at)<span class="{{ $it->es_anormal ? 'badge-red' : 'badge-green' }}">{{ $it->es_anormal ? 'ANORMAL' : 'Normal' }}</span><div class="text-xs text-slate-500">{{ $it->resultado_at->format('d/m H:i') }}</div>@else<span class="badge-yellow">Pendiente</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('laboratorio.index') }}" class="btn-secondary">Volver</a><button class="btn-primary">Guardar resultados</button></div>
</form>
@endsection
