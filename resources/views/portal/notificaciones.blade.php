@extends('layouts.app')
@section('title', 'Notificaciones')
@section('header', 'Comunicación · Notificaciones enviadas')

@section('content')
<div class="card mb-4">
    <form method="GET" class="card-body flex flex-wrap items-end gap-3">
        <div><label class="form-label" for="canal">Canal</label><select id="canal" name="canal" class="form-input w-36"><option value="">Todos</option><option value="email" @selected(request('canal') === 'email')>Correo</option><option value="sms" @selected(request('canal') === 'sms')>SMS</option></select></div>
        <div><label class="form-label" for="estado">Estado</label><select id="estado" name="estado" class="form-input w-36"><option value="">Todos</option>@foreach (['pendiente', 'enviado', 'fallido', 'abierto'] as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ ucfirst($e) }}</option>@endforeach</select></div>
        <div><label class="form-label" for="tipo">Tipo</label><select id="tipo" name="tipo" class="form-input w-48"><option value="">Todos</option>@foreach (['recordatorio_cita' => 'Recordatorio de cita', 'vacuna_vencida' => 'Vacuna vencida', 'receta_lista' => 'Receta lista', 'resultado_listo' => 'Resultado listo'] as $k => $v)<option value="{{ $k }}" @selected(request('tipo') === $k)>{{ $v }}</option>@endforeach</select></div>
        <button class="btn-secondary">Filtrar</button>
        <a href="{{ route('reportes.show', 'efectividad-notificaciones') }}" class="btn-secondary ml-auto">Reporte de efectividad</a>
    </form>
    @role('administrador')
    <form method="POST" action="{{ route('notificaciones.prueba') }}" class="flex flex-wrap items-center gap-2 border-t border-slate-200 px-5 py-3 text-sm">@csrf
        <span>Pasarela SMS actual: <strong>{{ $driver === 'android' ? 'Celular Android (SMS Gateway)' : 'Simulada (registro en log)' }}</strong>.</span>
        <input name="telefono" placeholder="9XXXXXXXX" maxlength="9" class="form-input w-36" required aria-label="Celular de prueba"><button class="btn-secondary btn-sm">Enviar SMS de prueba</button></form>
    @endrole
</div>
<div class="card">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Fecha</th><th>Canal</th><th>Tipo</th><th>Tutor</th><th>Paciente</th><th>Destinatario</th><th>Mensaje</th><th>Estado</th><th>Apertura</th></tr></thead>
            <tbody>
            @forelse ($notificaciones as $n)
                @php($cls = ['enviado' => 'badge-blue', 'abierto' => 'badge-green', 'fallido' => 'badge-red', 'pendiente' => 'badge-yellow'])
                <tr><td class="whitespace-nowrap">{{ $n->created_at->format('d/m/Y H:i') }}</td><td>{{ strtoupper($n->canal) }}</td><td class="text-xs">{{ str_replace('_', ' ', $n->tipo) }}</td><td class="text-xs">{{ $n->tutor?->nombre_completo }}</td><td class="text-xs">{{ $n->patient?->nombre_completo }}</td>
                    <td class="text-xs">{{ $n->destinatario }}</td><td class="max-w-sm text-xs">{{ \Illuminate\Support\Str::limit($n->mensaje, 120) }}</td>
                    <td><span class="{{ $cls[$n->estado] }}">{{ ucfirst($n->estado) }}</span>@if ($n->error)<div class="text-xs text-red-700">{{ \Illuminate\Support\Str::limit($n->error, 60) }}</div>@endif<div class="text-[10px] text-slate-500">{{ $n->proveedor }}</div></td>
                    <td class="text-xs">{{ $n->abierto_at?->format('d/m H:i') ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="9" class="py-6 text-center text-slate-500">Sin notificaciones.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $notificaciones->links() }}</div>
</div>
@endsection
