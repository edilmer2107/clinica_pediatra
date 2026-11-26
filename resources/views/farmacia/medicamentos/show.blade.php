@extends('layouts.app')
@section('title', $medicamento->nombre)
@section('header', 'Medicamento')

@section('content')
<div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-1">
        <div class="card-header"><h2 class="card-title">{{ $medicamento->nombre }} {{ $medicamento->concentracion }}</h2>
            @can('inventario.gestionar')<a href="{{ route('medicamentos.edit', $medicamento) }}" class="btn-secondary btn-sm">Editar</a>@endcan
        </div>
        <dl class="card-body grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
            <dt class="text-slate-500">Código</dt><dd>{{ $medicamento->codigo }}</dd>
            <dt class="text-slate-500">Principio activo</dt><dd>{{ $medicamento->principio_activo }}</dd>
            <dt class="text-slate-500">Forma</dt><dd>{{ $medicamento->forma_farmaceutica }}</dd>
            <dt class="text-slate-500">Presentación</dt><dd>{{ $medicamento->presentacion ?? '—' }}</dd>
            <dt class="text-slate-500">Rango mg/kg/día</dt><dd>{{ $medicamento->dosis_min_mg_kg_dia ?? '—' }} – {{ $medicamento->dosis_max_mg_kg_dia ?? '—' }}</dd>
            <dt class="text-slate-500">Máx. mg/día</dt><dd>{{ $medicamento->dosis_max_diaria_mg ?? '—' }}</dd>
            <dt class="text-slate-500">Precio</dt><dd>S/ {{ number_format($medicamento->precio_venta, 2) }}</dd>
            <dt class="text-slate-500">Stock vigente</dt><dd class="font-semibold">{{ $medicamento->stockTotal() }} {{ $medicamento->unidad }}</dd>
            <dt class="text-slate-500">Control</dt><dd>{{ $medicamento->es_controlado ? ucfirst($medicamento->tipo_control) : 'No' }}</dd>
        </dl>
        @if ($sugerido)
            <div class="alert-info mx-5 mb-5"><strong>Sugerencia FEFO:</strong> dispensar primero el lote <strong>{{ $sugerido->numero_lote }}</strong> (vence {{ $sugerido->fecha_vencimiento->format('d/m/Y') }}, {{ $sugerido->ubicacion }}).</div>
        @else
            <div class="alert-warning mx-5 mb-5">Sin lotes vigentes con stock.</div>
        @endif
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><h2 class="card-title">Lotes</h2>
            @can('inventario.gestionar')<a href="{{ route('lotes.create', ['medication_id' => $medicamento->id]) }}" class="btn-primary btn-sm">Ingresar lote</a>@endcan
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Lote</th><th>Vencimiento</th><th>Stock</th><th>Ubicación</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($medicamento->lots as $lote)
                    <tr>
                        <td class="font-mono text-xs">{{ $lote->numero_lote }}</td>
                        <td>
                            {{ $lote->fecha_vencimiento->format('d/m/Y') }}
                            @if ($lote->estaVencido())<span class="badge-red">Vencido</span>
                            @elseif ($lote->diasParaVencer() <= config('clinica.dias_alerta_vencimiento'))<span class="badge-yellow">{{ $lote->diasParaVencer() }} d</span>@endif
                        </td>
                        <td>{{ $lote->cantidad_actual }} / {{ $lote->cantidad_inicial }}</td>
                        <td class="text-xs">{{ $lote->almacen }}<br>{{ $lote->ubicacion }}</td>
                        <td>@include('farmacia.lotes._estado', ['lote' => $lote])</td>
                        <td>
                            @can('inventario.gestionar')
                                @if (in_array($lote->estado, ['activo', 'cuarentena']))
                                    <form method="POST" action="{{ route('lotes.estado', $lote) }}" class="flex gap-1">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="estado" value="{{ $lote->estado === 'activo' ? 'cuarentena' : 'activo' }}">
                                        <input name="motivo" required placeholder="Motivo" class="form-input w-28 py-1 text-xs">
                                        <button class="btn-secondary btn-sm">{{ $lote->estado === 'activo' ? 'Cuarentena' : 'Liberar' }}</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-slate-500">Sin lotes registrados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
