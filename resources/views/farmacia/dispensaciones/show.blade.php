@extends('layouts.app')
@section('title', 'Dispensación '.$dispensacion->codigo)
@section('header', 'Dispensación '.$dispensacion->codigo)

@section('content')
<div class="card max-w-4xl">
    <div class="card-header"><h2 class="card-title">Comprobante de dispensación</h2><button onclick="window.print()" class="btn-secondary btn-sm no-print">Imprimir</button></div>
    <dl class="card-body grid grid-cols-2 gap-2 text-sm md:grid-cols-4">
        <dt class="text-slate-500">Fecha</dt><dd>{{ $dispensacion->fecha->format('d/m/Y H:i') }}</dd>
        <dt class="text-slate-500">Receta</dt><dd>{{ $dispensacion->prescription->codigo }}</dd>
        <dt class="text-slate-500">Paciente</dt><dd>{{ $dispensacion->patient->nombre_completo }} ({{ $dispensacion->patient->historia_clinica }})</dd>
        <dt class="text-slate-500">Médico</dt><dd>{{ $dispensacion->prescription->doctor->name }}</dd>
        <dt class="text-slate-500">Farmacéutico</dt><dd>{{ $dispensacion->pharmacist->name }} {{ $dispensacion->pharmacist->colegiatura }}</dd>
        <dt class="text-slate-500">Retira</dt><dd>{{ $dispensacion->tutor ? $dispensacion->tutor->nombre_completo.' · DNI '.$dispensacion->tutor->dni : 'No identificado' }}</dd>
    </dl>
    <table class="table border-t border-slate-200">
        <thead><tr><th>Medicamento</th><th>Lote</th><th>Vence</th><th>Cantidad</th><th>P. unit.</th><th>Subtotal</th></tr></thead>
        <tbody>
        @foreach ($dispensacion->items as $i)
            <tr><td>{{ $i->medication->nombre_completo }}</td><td>{{ $i->lot->numero_lote }}</td><td>{{ $i->lot->fecha_vencimiento->format('d/m/Y') }}</td><td>{{ $i->cantidad }}</td><td>S/ {{ number_format($i->precio_unitario, 2) }}</td><td>S/ {{ number_format($i->cantidad * $i->precio_unitario, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <p class="px-5 py-3 text-xs text-slate-500">El importe se cargó a la cuenta única del paciente.</p>
</div>
@endsection
