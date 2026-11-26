@extends('layouts.app')
@section('title', 'Errores de medicación')
@section('header', 'Farmacia · Errores de dispensación y prescripción')

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="flex gap-2">
            <select name="tipo" class="form-input w-48"><option value="">Todos</option><option value="prescripcion" @selected(request('tipo') === 'prescripcion')>Prescripción</option><option value="dispensacion" @selected(request('tipo') === 'dispensacion')>Dispensación</option></select>
            <button class="btn-secondary">Filtrar</button>
        </form>
        <a href="{{ route('errores.create') }}" class="btn-primary">Registrar error</a>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Gravedad</th><th>Paciente</th><th>Descripción</th><th>Medida correctiva</th><th>Evidencia</th><th>Reportó</th></tr></thead>
            <tbody>
            @forelse ($errores as $e)
                <tr>
                    <td>{{ $e->fecha->format('d/m/Y') }}</td><td>{{ ucfirst($e->tipo) }}</td><td>{{ $categorias[$e->categoria] ?? $e->categoria }}</td>
                    <td><span class="{{ in_array($e->gravedad, ['moderado', 'grave']) ? 'badge-red' : 'badge-yellow' }}">{{ str_replace('_', ' ', $e->gravedad) }}</span></td>
                    <td>{{ $e->patient?->nombre_completo ?? '—' }}</td>
                    <td class="max-w-xs text-xs">{{ $e->descripcion }}</td>
                    <td class="max-w-xs text-xs">{{ $e->medida_correctiva }}</td>
                    <td>@if ($e->evidencia_path)<a href="{{ route('errores.evidencia', $e) }}" class="text-brand-700 hover:underline">Descargar</a>@else — @endif</td>
                    <td class="text-xs">{{ $e->reportadoPor?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-6 text-center text-slate-500">Sin errores registrados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $errores->links() }}</div>
</div>
@endsection
