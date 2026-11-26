@extends('layouts.app')
@section('title', 'Lotes y stock')
@section('header', 'Farmacia · Lotes y stock')

@section('content')
<div class="card">
    <div class="card-header flex-wrap">
        <form method="GET" class="flex flex-wrap gap-2">
            <select name="medication_id" class="form-input w-64">
                <option value="">Todos los medicamentos</option>
                @foreach ($medicamentos as $m)<option value="{{ $m->id }}" @selected($medicamentoId == $m->id)>{{ $m->nombre }} {{ $m->concentracion }}</option>@endforeach
            </select>
            <select name="filtro" class="form-input w-48">
                @foreach (['todos' => 'Todos', 'por_vencer' => 'Por vencer (≤ '.config('clinica.dias_alerta_vencimiento').' d)', 'vencidos' => 'Vencidos con stock'] + \App\Models\Lot::ESTADOS as $k => $v)
                    <option value="{{ $k }}" @selected($filtro === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn-secondary">Filtrar</button>
        </form>
        @can('inventario.gestionar')<a href="{{ route('lotes.create') }}" class="btn-primary">Ingresar lote</a>@endcan
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Medicamento</th><th>Lote</th><th>Vencimiento</th><th>Stock</th><th>Almacén / ubicación</th><th>Proveedor</th><th>Estado</th></tr></thead>
            <tbody>
            @forelse ($lotes as $lote)
                <tr>
                    <td><a class="text-brand-700 hover:underline" href="{{ route('medicamentos.show', $lote->medication_id) }}">{{ $lote->medication->nombre }} {{ $lote->medication->concentracion }}</a></td>
                    <td class="font-mono text-xs">{{ $lote->numero_lote }}</td>
                    <td>{{ $lote->fecha_vencimiento->format('d/m/Y') }}
                        @if ($lote->estaVencido())<span class="badge-red">Vencido</span>
                        @elseif ($lote->diasParaVencer() <= config('clinica.dias_alerta_vencimiento'))<span class="badge-yellow">{{ $lote->diasParaVencer() }} d</span>@endif
                    </td>
                    <td>{{ $lote->cantidad_actual }}</td>
                    <td class="text-xs">{{ $lote->almacen }} · {{ $lote->ubicacion }}</td>
                    <td class="text-xs">{{ $lote->proveedor }}</td>
                    <td>@include('farmacia.lotes._estado')</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-slate-500">No hay lotes con ese criterio.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $lotes->links() }}</div>
</div>
@endsection
