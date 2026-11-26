@extends('layouts.app')
@section('title', 'Cuenta de '.$cuenta->patient->nombre_completo)
@section('header', 'Cuenta única · '.$cuenta->patient->nombre_completo)

@section('content')
<div class="mb-4 grid gap-4 md:grid-cols-4">
    <div class="stat"><div class="stat-label">Servicios médicos</div><div class="stat-value">S/ {{ number_format($totales['servicios'], 2) }}</div></div>
    <div class="stat"><div class="stat-label">Medicamentos</div><div class="stat-value">S/ {{ number_format($totales['medicamentos'], 2) }}</div></div>
    <div class="stat"><div class="stat-label">IGV incluido (18 %)</div><div class="stat-value">S/ {{ number_format($totales['igv'], 2) }}</div></div>
    <div class="stat"><div class="stat-label">Total a facturar</div><div class="stat-value text-brand-700">S/ {{ number_format($totales['total'], 2) }}</div></div>
</div>
<div class="card">
    <div class="card-header"><h2 class="card-title">Cargos pendientes de facturar ({{ $pendientes->count() }})</h2>
        @if ($cuenta->estado === 'abierta' && $pendientes->isNotEmpty())@can('facturacion.gestionar')
            <form method="POST" action="{{ route('cuentas.facturar', $cuenta) }}" class="flex gap-2" onsubmit="return confirm('¿Emitir el comprobante consolidado?')">@csrf
                <select name="tutor_id" class="form-input w-60" aria-label="Cliente">@foreach ($cuenta->patient->tutors as $t)<option value="{{ $t->id }}">{{ $t->nombre_completo }} · DNI {{ $t->dni }}</option>@endforeach</select>
                <button class="btn-primary">Emitir comprobante único</button></form>
        @endcan @endif
    </div>
    <table class="table">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Cant.</th><th>P. unit.</th><th>Subtotal</th></tr></thead>
        <tbody>
        @forelse ($pendientes->sortBy('created_at') as $c)
            <tr><td>{{ $c->created_at->format('d/m/Y') }}</td><td><span class="{{ $c->tipo === 'medicamento' ? 'badge-blue' : 'badge-gray' }}">{{ ucfirst($c->tipo) }}</span></td><td>{{ $c->descripcion }}</td><td>{{ (float) $c->cantidad }}</td><td>S/ {{ number_format($c->precio_unitario, 2) }}</td><td>S/ {{ number_format($c->subtotal, 2) }}</td></tr>
        @empty
            <tr><td colspan="6" class="py-6 text-center text-slate-500">No hay cargos pendientes.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@if ($cuenta->invoices->isNotEmpty())
<div class="card mt-4"><div class="card-header"><h2 class="card-title">Comprobantes de esta cuenta</h2></div>
    <ul class="divide-y divide-slate-100 text-sm">@foreach ($cuenta->invoices as $f)<li class="flex justify-between px-5 py-2"><a href="{{ route('facturas.show', $f) }}" class="text-brand-700 hover:underline">{{ $f->numero }}</a><span>S/ {{ number_format($f->total, 2) }} · {{ ucfirst($f->estado) }}</span></li>@endforeach</ul></div>
@endif
@endsection
