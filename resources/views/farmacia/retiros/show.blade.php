@extends('layouts.app')
@section('title', 'Trazabilidad lote '.$lote->numero_lote)
@section('header', 'Trazabilidad del lote '.$lote->numero_lote)

@section('content')
<div class="mb-4 grid gap-4 md:grid-cols-4">
    <div class="stat"><div class="stat-label">Medicamento</div><div class="mt-1 font-semibold">{{ $lote->medication->nombre_completo }}</div></div>
    <div class="stat"><div class="stat-label">Vencimiento</div><div class="stat-value text-lg">{{ $lote->fecha_vencimiento->format('d/m/Y') }}</div></div>
    <div class="stat"><div class="stat-label">Stock actual</div><div class="stat-value text-lg">{{ $lote->cantidad_actual }}</div></div>
    <div class="stat"><div class="stat-label">Estado</div><div class="mt-2">@include('farmacia.lotes._estado')</div></div>
</div>

<div class="card mb-6">
    <div class="card-header"><h2 class="card-title">Pacientes y tutores que recibieron el lote ({{ $receptores->count() }})</h2><button onclick="window.print()" class="btn-secondary btn-sm no-print">Imprimir</button></div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Origen</th><th>Paciente</th><th>Cant.</th><th>Tutores (contacto)</th></tr></thead>
            <tbody>
            @forelse ($receptores as $r)
                <tr>
                    <td>{{ $r['fecha']->format('d/m/Y') }}</td>
                    <td>{{ $r['origen'] }}</td>
                    <td>{{ $r['paciente']->nombre_completo }}<div class="text-xs text-slate-500">{{ $r['paciente']->historia_clinica }}</div></td>
                    <td>{{ $r['cantidad'] }}</td>
                    <td class="text-xs">@foreach ($r['tutores'] as $t){{ $t->nombre_completo }} ({{ $t->pivot->parentesco }}) · {{ $t->telefono }} {{ $t->email }}<br>@endforeach</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-slate-500">Ningún paciente recibió este lote.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if ($lote->estado !== 'retirado')
<form method="POST" action="{{ route('retiros.store', $lote) }}" class="card no-print max-w-3xl" onsubmit="return confirm('¿Confirma el retiro del lote? El stock restante saldrá del inventario.')">
    @csrf
    <div class="card-header"><h2 class="card-title">Registrar retiro del mercado</h2></div>
    <div class="card-body grid gap-4 md:grid-cols-2">
        <div><label class="form-label" for="alerta_sanitaria">N° de alerta sanitaria (DIGEMID) *</label><input id="alerta_sanitaria" name="alerta_sanitaria" value="{{ old('alerta_sanitaria') }}" class="form-input" required></div>
        <div><label class="form-label" for="disposicion_final">Disposición final *</label>
            <select id="disposicion_final" name="disposicion_final" class="form-input" required>
                <option value="devolucion_proveedor">Devolución al proveedor</option><option value="destruccion">Destrucción</option><option value="cuarentena">Cuarentena en almacén</option>
            </select></div>
        <div class="md:col-span-2"><label class="form-label" for="motivo">Motivo *</label><textarea id="motivo" name="motivo" rows="2" class="form-input" required>{{ old('motivo') }}</textarea></div>
        <div class="md:col-span-2"><label class="form-label" for="detalle_disposicion">Detalle de la disposición</label><textarea id="detalle_disposicion" name="detalle_disposicion" rows="2" class="form-input">{{ old('detalle_disposicion') }}</textarea></div>
    </div>
    <div class="flex justify-end border-t border-slate-200 px-5 py-3"><button class="btn-danger">Retirar lote</button></div>
</form>
@endif
@endsection
