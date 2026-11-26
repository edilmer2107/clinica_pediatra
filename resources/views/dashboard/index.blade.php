@extends('layouts.app')

@section('title', 'Panel principal')
@section('header', 'Panel principal')

@section('content')
<p class="mb-5 text-sm text-slate-600">Bienvenido(a), <strong>{{ auth()->user()->name }}</strong>. Hoy es {{ now()->translatedFormat('l d \d\e F \d\e Y') }}.</p>

<div class="grid grid-cols-2 gap-4 md:grid-cols-4">
    @can('pacientes.ver')
        <div class="stat"><div class="stat-label">Pacientes registrados</div><div class="stat-value">{{ $indicadores['pacientes'] }}</div></div>
    @endcan
    @can('citas.ver')
        <div class="stat"><div class="stat-label">Citas pendientes hoy</div><div class="stat-value">{{ $indicadores['citas_hoy'] }}</div></div>
    @endcan
    @can('ficha.ver')
        <div class="stat"><div class="stat-label">Alertas de crecimiento (30 d)</div><div class="stat-value {{ $indicadores['alertas_crecimiento'] ? 'text-red-600' : '' }}">{{ $indicadores['alertas_crecimiento'] }}</div></div>
    @endcan
    @can('recetas.ver')
        <div class="stat"><div class="stat-label">Recetas por dispensar</div><div class="stat-value">{{ $indicadores['recetas_pendientes'] }}</div></div>
    @endcan
    @can('inventario.ver')
        <div class="stat"><div class="stat-label">Lotes por vencer (≤ {{ config('clinica.dias_alerta_vencimiento') }} d)</div><div class="stat-value text-amber-600">{{ $indicadores['lotes_por_vencer'] }}</div></div>
        <div class="stat"><div class="stat-label">Lotes vencidos con stock</div><div class="stat-value {{ $indicadores['lotes_vencidos'] ? 'text-red-600' : '' }}">{{ $indicadores['lotes_vencidos'] }}</div></div>
    @endcan
    @can('facturacion.ver')
        <div class="stat"><div class="stat-label">Cuentas por cobrar</div><div class="stat-value">S/ {{ number_format($indicadores['por_cobrar'], 2) }}</div></div>
    @endcan
</div>

@if ($proximasCitas->isNotEmpty())
<div class="card mt-6">
    <div class="card-header"><h2 class="card-title">Próximas citas</h2></div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Fecha y hora</th><th>Paciente</th><th>Médico</th><th>Modalidad</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach ($proximasCitas as $cita)
                <tr>
                    <td>{{ $cita->fecha_hora->format('d/m/Y H:i') }}</td>
                    <td>{{ $cita->patient->nombre_completo }}</td>
                    <td>{{ $cita->doctor->name }}</td>
                    <td><span class="{{ $cita->modalidad === 'telemedicina' ? 'badge-blue' : 'badge-gray' }}">{{ ucfirst($cita->modalidad) }}</span></td>
                    <td>{{ \App\Models\Appointment::ESTADOS[$cita->estado] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
