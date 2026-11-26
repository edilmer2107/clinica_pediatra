@extends('layouts.app')
@section('title', 'Comprobantes')
@section('header', 'Facturación · Comprobantes')

@section('content')
<div class="card">
    <div class="card-header"><form method="GET" class="flex gap-2"><input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Número o apellido" class="form-input w-60">
        <select name="estado" class="form-input w-40"><option value="">Todos</option>@foreach (['pendiente', 'parcial', 'pagada', 'anulada'] as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ ucfirst($e) }}</option>@endforeach</select><button class="btn-secondary">Filtrar</button></form>
        <a href="{{ route('cuentas.index') }}" class="btn-primary">Facturar cuentas abiertas</a></div>
    <table class="table">
        <thead><tr><th>Número</th><th>Emisión</th><th>Paciente</th><th>Servicios</th><th>Medicamentos</th><th>Total</th><th>Saldo</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($facturas as $f)
            @php($cls = ['pendiente' => 'badge-yellow', 'parcial' => 'badge-blue', 'pagada' => 'badge-green', 'anulada' => 'badge-red'])
            <tr><td class="font-mono text-xs">{{ $f->numero }}</td><td>{{ $f->fecha_emision->format('d/m/Y') }}</td><td>{{ $f->patient->nombre_completo }}</td><td>S/ {{ number_format($f->subtotal_servicios, 2) }}</td><td>S/ {{ number_format($f->subtotal_medicamentos, 2) }}</td>
                <td class="font-semibold">S/ {{ number_format($f->total, 2) }}</td><td>S/ {{ number_format($f->estado === 'anulada' ? 0 : $f->saldo(), 2) }}</td><td><span class="{{ $cls[$f->estado] }}">{{ ucfirst($f->estado) }}</span></td>
                <td class="text-right"><a href="{{ route('facturas.show', $f) }}" class="btn-secondary btn-sm">Ver</a></td></tr>
        @empty
            <tr><td colspan="9" class="py-6 text-center text-slate-500">Sin comprobantes.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $facturas->links() }}</div>
</div>
@endsection
