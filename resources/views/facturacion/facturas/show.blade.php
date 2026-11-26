@extends('layouts.app')
@section('title', $factura->numero)
@section('header', 'Comprobante '.$factura->numero)

@section('content')
<div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="card-header"><div><h2 class="card-title">Boleta de venta electrónica {{ $factura->numero }}</h2><p class="text-xs text-slate-500">Emitida {{ $factura->fecha_emision->format('d/m/Y') }} · vence {{ $factura->fecha_vencimiento->format('d/m/Y') }}</p></div>
            <div class="no-print flex gap-2"><a href="{{ route('facturas.pdf', $factura) }}" class="btn-secondary btn-sm">PDF</a><button onclick="window.print()" class="btn-secondary btn-sm">Imprimir</button></div></div>
        @if ($factura->estado === 'anulada')<div class="alert-error m-5">ANULADA el {{ $factura->anulada_at->format('d/m/Y H:i') }}. Motivo: {{ $factura->motivo_anulacion }}</div>@endif
        <dl class="card-body grid grid-cols-2 gap-2 text-sm">
            <dt class="text-slate-500">Paciente</dt><dd>{{ $factura->patient->nombre_completo }} ({{ $factura->patient->historia_clinica }})</dd>
            <dt class="text-slate-500">Cliente</dt><dd>{{ $factura->tutor?->nombre_completo }} · DNI {{ $factura->tutor?->dni }}</dd>
        </dl>
        <table class="table border-t border-slate-200">
            <thead><tr><th>Tipo</th><th>Descripción</th><th>Cant.</th><th>P. unit.</th><th>Importe</th></tr></thead>
            <tbody>@foreach ($factura->charges as $c)<tr><td>{{ ucfirst($c->tipo) }}</td><td>{{ $c->descripcion }}</td><td>{{ (float) $c->cantidad }}</td><td>S/ {{ number_format($c->precio_unitario, 2) }}</td><td>S/ {{ number_format($c->subtotal, 2) }}</td></tr>@endforeach</tbody>
        </table>
        <div class="flex justify-end p-5"><dl class="grid w-72 grid-cols-2 gap-1 text-sm">
            <dt>Servicios médicos</dt><dd class="text-right">S/ {{ number_format($factura->subtotal_servicios, 2) }}</dd>
            <dt>Medicamentos</dt><dd class="text-right">S/ {{ number_format($factura->subtotal_medicamentos, 2) }}</dd>
            <dt>Op. gravada</dt><dd class="text-right">S/ {{ number_format($factura->subtotal, 2) }}</dd>
            <dt>IGV 18 %</dt><dd class="text-right">S/ {{ number_format($factura->igv, 2) }}</dd>
            <dt class="font-bold">TOTAL</dt><dd class="text-right font-bold">S/ {{ number_format($factura->total, 2) }}</dd>
            <dt>Pagado</dt><dd class="text-right">S/ {{ number_format($factura->monto_pagado, 2) }}</dd>
            <dt class="font-semibold">Saldo</dt><dd class="text-right font-semibold">S/ {{ number_format($factura->estado === 'anulada' ? 0 : $factura->saldo(), 2) }}</dd>
        </dl></div>
    </div>
    <div class="space-y-4 no-print">
        <div class="card"><div class="card-header"><h3 class="card-title">Pagos</h3></div>
            <ul class="divide-y divide-slate-100 text-sm">@forelse ($factura->payments as $p)<li class="px-5 py-2">{{ $p->fecha->format('d/m/Y H:i') }} · S/ {{ number_format($p->monto, 2) }} · {{ ucfirst($p->metodo) }} {{ $p->referencia }}<div class="text-xs text-slate-500">{{ $p->user?->name }}</div></li>@empty<li class="px-5 py-3 text-slate-500">Sin pagos.</li>@endforelse</ul>
            @if (in_array($factura->estado, ['pendiente', 'parcial']))@can('facturacion.gestionar')
            <form method="POST" action="{{ route('facturas.pagar', $factura) }}" class="space-y-2 border-t border-slate-200 p-5">@csrf
                <input type="number" step="0.01" min="0.01" max="{{ $factura->saldo() }}" name="monto" value="{{ $factura->saldo() }}" class="form-input" required aria-label="Monto">
                <select name="metodo" class="form-input" aria-label="Método">@foreach (['efectivo', 'tarjeta', 'yape', 'plin', 'transferencia'] as $m)<option value="{{ $m }}">{{ ucfirst($m) }}</option>@endforeach</select>
                <input name="referencia" placeholder="N° de operación (opcional)" class="form-input">
                <button class="btn-success w-full">Registrar pago</button></form>
            @endcan @endif
        </div>
        @if ($factura->estado !== 'anulada')@can('facturacion.anular')
        <form method="POST" action="{{ route('facturas.anular', $factura) }}" class="card" onsubmit="return confirm('¿Anular el comprobante? Esta acción queda registrada en la auditoría.')">@csrf @method('PATCH')
            <div class="card-body space-y-2"><input name="motivo" required minlength="5" placeholder="Motivo de anulación" class="form-input"><button class="btn-danger w-full">Anular comprobante</button></div></form>
        @endcan @endif
    </div>
</div>
@endsection
