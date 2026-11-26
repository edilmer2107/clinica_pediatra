@extends('layouts.app')
@section('title', 'Conciliación de inventario')
@section('header', 'Farmacia · Conciliación de inventario')

@section('content')
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Conciliaciones (stock teórico vs. físico)</h2>
        <form method="POST" action="{{ route('conciliaciones.store') }}">@csrf<button class="btn-primary">Iniciar nueva conciliación</button></form>
    </div>
    <table class="table">
        <thead><tr><th>#</th><th>Fecha</th><th>Stock teórico</th><th>Stock físico</th><th>Consistencia</th><th>Estado</th><th>Responsable</th><th></th></tr></thead>
        <tbody>
        @forelse ($conteos as $c)
            <tr>
                <td>{{ $c->id }}</td><td>{{ $c->fecha->format('d/m/Y') }}</td><td>{{ $c->stock_teorico_total }}</td><td>{{ $c->stock_fisico_total }}</td>
                <td>@if (! is_null($c->consistencia))<span class="{{ $c->consistencia >= 99 ? 'badge-green' : 'badge-red' }}">{{ number_format($c->consistencia, 2) }} %</span>@else — @endif</td>
                <td><span class="{{ $c->estado === 'cerrado' ? 'badge-gray' : 'badge-yellow' }}">{{ ucfirst($c->estado) }}</span></td>
                <td>{{ $c->user?->name }}</td>
                <td class="text-right"><a href="{{ route('conciliaciones.show', $c) }}" class="btn-secondary btn-sm">{{ $c->estado === 'cerrado' ? 'Ver' : 'Continuar' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="py-6 text-center text-slate-500">Sin conciliaciones.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $conteos->links() }}</div>
</div>
@endsection
