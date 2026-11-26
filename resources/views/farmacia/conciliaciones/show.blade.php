@extends('layouts.app')
@section('title', 'Conciliación #'.$conteo->id)
@section('header', 'Conciliación de inventario #'.$conteo->id)

@section('content')
<div class="mb-4 grid gap-4 md:grid-cols-4">
    <div class="stat"><div class="stat-label">Stock teórico</div><div class="stat-value">{{ $conteo->stock_teorico_total }}</div></div>
    <div class="stat"><div class="stat-label">Stock físico contado</div><div class="stat-value">{{ $conteo->stock_fisico_total }}</div></div>
    <div class="stat"><div class="stat-label">Consistencia (meta ≥ {{ $meta }} %)</div><div class="stat-value {{ ! is_null($conteo->consistencia) && $conteo->consistencia < $meta ? 'text-red-600' : 'text-emerald-600' }}">{{ is_null($conteo->consistencia) ? '—' : number_format($conteo->consistencia, 2).' %' }}</div></div>
    <div class="stat"><div class="stat-label">Estado</div><div class="stat-value text-lg">{{ ucfirst($conteo->estado) }}</div></div>
</div>

<form method="POST" action="{{ route('conciliaciones.update', $conteo) }}" class="card">
    @csrf @method('PUT')
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Medicamento</th><th>Lote</th><th>Ubicación</th><th>Teórico</th><th>Físico</th><th>Diferencia</th></tr></thead>
            <tbody>
            @foreach ($conteo->items as $item)
                <tr>
                    <td>{{ $item->lot->medication->nombre }} {{ $item->lot->medication->concentracion }}</td>
                    <td class="font-mono text-xs">{{ $item->lot->numero_lote }}</td>
                    <td class="text-xs">{{ $item->lot->ubicacion }}</td>
                    <td>{{ $item->stock_teorico }}</td>
                    <td>
                        @if ($conteo->estado === 'cerrado'){{ $item->stock_fisico }}
                        @else<input type="number" min="0" name="fisico[{{ $item->id }}]" value="{{ old('fisico.'.$item->id, $item->stock_fisico) }}" class="form-input w-24">@endif
                    </td>
                    <td>@if (! is_null($item->diferencia))<span class="{{ $item->diferencia === 0 ? 'badge-green' : 'badge-red' }}">{{ $item->diferencia > 0 ? '+' : '' }}{{ $item->diferencia }}</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @if ($conteo->estado !== 'cerrado')
        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 px-5 py-3">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ajustar_stock" value="1"> Ajustar stock del sistema al cerrar</label>
            <button name="accion" value="guardar" class="btn-secondary">Guardar avance</button>
            <button name="accion" value="cerrar" class="btn-primary">Cerrar conciliación</button>
        </div>
    @endif
</form>
@endsection
