@extends('layouts.app')
@section('title', 'Bitácora de auditoría')
@section('header', 'Administración · Bitácora de auditoría (inmutable)')

@section('content')
<div class="card mb-4">
    <form method="GET" class="card-body flex flex-wrap items-end gap-3">
        <div><label class="form-label" for="modulo">Módulo</label><select id="modulo" name="modulo" class="form-input w-40"><option value="">Todos</option>@foreach ($modulos as $m)<option @selected(request('modulo') === $m)>{{ $m }}</option>@endforeach</select></div>
        <div><label class="form-label" for="accion">Acción</label><select id="accion" name="accion" class="form-input w-52"><option value="">Todas</option>@foreach ($acciones as $a)<option @selected(request('accion') === $a)>{{ $a }}</option>@endforeach</select></div>
        <div><label class="form-label" for="usuario">Usuario</label><input id="usuario" name="usuario" value="{{ request('usuario') }}" class="form-input w-44"></div>
        <div><label class="form-label" for="desde">Desde</label><input id="desde" type="date" name="desde" value="{{ request('desde') }}" class="form-input"></div>
        <div><label class="form-label" for="hasta">Hasta</label><input id="hasta" type="date" name="hasta" value="{{ request('hasta') }}" class="form-input"></div>
        <button class="btn-secondary">Filtrar</button>
    </form>
    <form method="POST" action="{{ route('auditoria.verificar') }}" class="flex items-center justify-between border-t border-slate-200 px-5 py-3 text-sm">@csrf
        <span class="text-slate-600">Cada registro guarda el hash SHA-256 del anterior. Los registros no pueden modificarse ni eliminarse.</span>
        <button class="btn-primary btn-sm">Verificar integridad de la cadena</button></form>
</div>
<div class="card">
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>#</th><th>Fecha y hora</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Detalle</th><th>IP</th><th>Hash</th></tr></thead>
            <tbody>
            @foreach ($registros as $r)
                <tr><td>{{ $r->id }}</td><td class="whitespace-nowrap">{{ $r->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $r->user_name ?? 'Sistema / anónimo' }}</td><td>{{ $r->modulo }}</td><td><span class="badge-gray">{{ $r->accion }}</span></td>
                    <td class="max-w-md text-xs">{{ $r->descripcion }}</td><td class="text-xs">{{ $r->ip }}</td><td class="font-mono text-[10px] text-slate-500" title="{{ $r->hash }}">{{ substr($r->hash, 0, 12) }}…</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $registros->links() }}</div>
</div>
@endsection
