@extends('layouts.app')
@section('title', 'Retiros de mercado')
@section('header', 'Farmacia · Trazabilidad inversa y retiros de mercado')

@section('content')
<div class="card mb-6">
    <div class="card-header"><h2 class="card-title">Buscar lote afectado por alerta sanitaria</h2></div>
    <div class="card-body">
        <form method="GET" class="flex gap-2"><input name="lote" value="{{ $buscar }}" placeholder="Número de lote" class="form-input w-72" required><button class="btn-primary">Buscar</button></form>
        @if ($buscar !== '')
            <table class="table mt-4">
                <thead><tr><th>Lote</th><th>Medicamento</th><th>Vencimiento</th><th>Stock</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($lotesEncontrados as $lote)
                    <tr><td class="font-mono text-xs">{{ $lote->numero_lote }}</td><td>{{ $lote->medication->nombre }} {{ $lote->medication->concentracion }}</td><td>{{ $lote->fecha_vencimiento->format('d/m/Y') }}</td><td>{{ $lote->cantidad_actual }}</td><td>@include('farmacia.lotes._estado')</td>
                        <td class="text-right"><a href="{{ route('retiros.show', $lote) }}" class="btn-primary btn-sm">Ver trazabilidad</a></td></tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-center text-slate-500">No se encontraron lotes.</td></tr>
                @endforelse
                </tbody>
            </table>
        @endif
    </div>
</div>
<div class="card">
    <div class="card-header"><h2 class="card-title">Retiros registrados</h2></div>
    <table class="table">
        <thead><tr><th>Fecha</th><th>Alerta</th><th>Lote</th><th>Medicamento</th><th>Cantidad</th><th>Disposición final</th><th>Registró</th></tr></thead>
        <tbody>
        @forelse ($retiros as $r)
            <tr><td>{{ $r->fecha->format('d/m/Y') }}</td><td>{{ $r->alerta_sanitaria }}</td><td><a class="text-brand-700 hover:underline" href="{{ route('retiros.show', $r->lot) }}">{{ $r->lot->numero_lote }}</a></td><td>{{ $r->lot->medication->nombre }}</td><td>{{ $r->cantidad_retirada }}</td><td>{{ str_replace('_', ' ', ucfirst($r->disposicion_final)) }}</td><td class="text-xs">{{ $r->registradoPor?->name }}</td></tr>
        @empty
            <tr><td colspan="7" class="py-6 text-center text-slate-500">Sin retiros registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $retiros->links() }}</div>
</div>
@endsection
