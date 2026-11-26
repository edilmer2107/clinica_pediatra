@extends('layouts.app')
@section('title', 'Libro de controlados')
@section('header', 'Farmacia · Libro de estupefacientes y psicotrópicos')

@section('content')
<div class="card">
    <div class="card-header flex-wrap no-print">
        <form method="GET" class="flex flex-wrap gap-2">
            <select name="medication_id" class="form-input w-72">
                @foreach ($controlados as $c)<option value="{{ $c->id }}" @selected($medicamento?->id === $c->id)>{{ $c->nombre }} {{ $c->concentracion }} — {{ ucfirst($c->tipo_control) }}</option>@endforeach
            </select>
            <input type="date" name="desde" value="{{ request('desde') }}" class="form-input w-40">
            <input type="date" name="hasta" value="{{ request('hasta') }}" class="form-input w-40">
            <button class="btn-secondary">Ver libro</button>
        </form>
        @if ($medicamento)<a href="{{ route('reportes.show', ['reporte' => 'libro-controlados', 'medication_id' => $medicamento->id]) }}" class="btn-primary">Libro oficial (PDF/Excel)</a>@endif
    </div>
    @if ($medicamento)
        <div class="card-body text-sm"><strong>{{ $medicamento->nombre }} {{ $medicamento->concentracion }}</strong> · {{ ucfirst($medicamento->tipo_control) }} · Saldo actual: <strong>{{ $movimientos->last()?->saldo ?? 0 }}</strong> {{ $medicamento->unidad }}</div>
    @endif
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Lote</th><th>Documento</th><th>Entrada</th><th>Salida</th><th>Saldo</th><th>Farmacéutico</th><th>Tutor que retira</th><th>Paciente</th></tr></thead>
            <tbody>
            @forelse ($movimientos as $m)
                <tr>
                    <td>{{ $m->fecha->format('d/m/Y H:i') }}</td>
                    <td><span class="{{ $m->tipo === 'entrada' ? 'badge-green' : 'badge-yellow' }}">{{ ucfirst($m->tipo) }}</span></td>
                    <td class="font-mono text-xs">{{ $m->lot->numero_lote }}</td>
                    <td class="text-xs">{{ $m->documento }}</td>
                    <td>{{ $m->tipo === 'entrada' ? $m->cantidad : '' }}</td>
                    <td>{{ $m->tipo === 'salida' ? $m->cantidad : '' }}</td>
                    <td class="font-semibold">{{ $m->saldo }}</td>
                    <td class="text-xs">{{ $m->pharmacist->name }}</td>
                    <td class="text-xs">{{ $m->tutor?->nombre_completo }} {{ $m->tutor_dni ? 'DNI '.$m->tutor_dni : '' }}</td>
                    <td class="text-xs">{{ $m->patient?->nombre_completo }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="py-6 text-center text-slate-500">Sin movimientos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
