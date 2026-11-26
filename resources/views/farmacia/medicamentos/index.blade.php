@extends('layouts.app')
@section('title', 'Medicamentos')
@section('header', 'Farmacia · Medicamentos')

@section('content')
<div class="card">
    <div class="card-header flex-wrap">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Nombre, principio activo o código" class="form-input w-72">
            <select name="tipo" class="form-input w-44">
                <option value="">Todos</option>
                <option value="controlados" @selected($tipo === 'controlados')>Controlados</option>
                <option value="vacunas" @selected($tipo === 'vacunas')>Vacunas</option>
            </select>
            <button class="btn-secondary">Filtrar</button>
        </form>
        @can('inventario.gestionar')
            <div class="flex gap-2">
                <a href="{{ route('lotes.create') }}" class="btn-secondary">Ingresar lote</a>
                <a href="{{ route('medicamentos.create') }}" class="btn-primary">Nuevo medicamento</a>
            </div>
        @endcan
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Código</th><th>Medicamento</th><th>Principio activo</th><th>Rango pediátrico</th><th>Precio</th><th>Stock vigente</th><th></th></tr></thead>
            <tbody>
            @forelse ($medicamentos as $m)
                <tr>
                    <td class="font-mono text-xs">{{ $m->codigo }}</td>
                    <td>
                        <div class="font-medium">{{ $m->nombre }} {{ $m->concentracion }}</div>
                        <div class="text-xs text-slate-500">{{ $m->forma_farmaceutica }} · {{ $m->presentacion }}</div>
                        @if ($m->es_controlado)<span class="badge-red mt-1">{{ ucfirst($m->tipo_control) }}</span>@endif
                        @if ($m->es_vacuna)<span class="badge-blue mt-1">Vacuna</span>@endif
                        @unless ($m->activo)<span class="badge-gray mt-1">Inactivo</span>@endunless
                    </td>
                    <td>{{ $m->principio_activo }}</td>
                    <td class="text-xs">
                        @if ($m->dosis_max_mg_kg_dia){{ rtrim(rtrim(number_format($m->dosis_min_mg_kg_dia, 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($m->dosis_max_mg_kg_dia, 2), '0'), '.') }} mg/kg/día @elseif ($m->dosis_max_mg_m2_dia) {{ $m->dosis_min_mg_m2_dia }}–{{ $m->dosis_max_mg_m2_dia }} mg/m²/día @else — @endif
                    </td>
                    <td>S/ {{ number_format($m->precio_venta, 2) }}</td>
                    <td>
                        @php($stock = (int) $m->stock)
                        <span class="{{ $stock <= $m->stock_minimo ? 'badge-red' : 'badge-green' }}">{{ $stock }} {{ $m->unidad }}</span>
                    </td>
                    <td class="text-right"><a href="{{ route('medicamentos.show', $m) }}" class="btn-secondary btn-sm">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-6 text-center text-slate-500">No se encontraron medicamentos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $medicamentos->links() }}</div>
</div>
@endsection
