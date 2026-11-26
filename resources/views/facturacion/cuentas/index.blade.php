@extends('layouts.app')
@section('title', 'Cuentas de pacientes')
@section('header', 'Facturación · Cuentas únicas por paciente')

@section('content')
<div class="card">
    <div class="card-header"><form method="GET" class="flex gap-2"><input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Apellido o HC" class="form-input w-60">
        <select name="estado" class="form-input w-40"><option value="abierta" @selected(request('estado', 'abierta') === 'abierta')>Abiertas</option><option value="facturada" @selected(request('estado') === 'facturada')>Facturadas</option></select><button class="btn-secondary">Filtrar</button></form></div>
    <table class="table">
        <thead><tr><th>Paciente</th><th>Abierta desde</th><th>Cargos sin facturar</th><th>Monto pendiente</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($cuentas as $c)
            <tr><td>{{ $c->patient->nombre_completo }} <span class="text-xs text-slate-500">{{ $c->patient->historia_clinica }}</span></td><td>{{ $c->abierta_at->format('d/m/Y') }}</td><td>{{ $c->cargos_pendientes }}</td>
                <td class="font-semibold">S/ {{ number_format($c->monto_pendiente ?? 0, 2) }}</td><td><span class="{{ $c->estado === 'abierta' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($c->estado) }}</span></td>
                <td class="text-right"><a href="{{ route('cuentas.show', $c) }}" class="btn-secondary btn-sm">Ver cuenta</a></td></tr>
        @empty
            <tr><td colspan="6" class="py-6 text-center text-slate-500">Sin cuentas.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $cuentas->links() }}</div>
</div>
@endsection
