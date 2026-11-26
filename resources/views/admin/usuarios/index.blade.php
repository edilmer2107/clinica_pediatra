@extends('layouts.app')
@section('title', 'Usuarios')
@section('header', 'Administración · Usuarios y roles')

@section('content')
<div class="card">
    <div class="card-header"><form method="GET" class="flex gap-2"><input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o correo" class="form-input w-60">
        <select name="rol" class="form-input w-48"><option value="">Todos los roles</option>@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected(request('rol') === $k)>{{ $v }}</option>@endforeach</select><button class="btn-secondary">Filtrar</button></form>
        <a href="{{ route('usuarios.create') }}" class="btn-primary">Nuevo usuario</a></div>
    <table class="table">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Colegiatura</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @foreach ($usuarios as $u)
            <tr><td>{{ $u->name }}</td><td>{{ $u->email }}</td><td>{{ $u->rolPrincipal() }}</td><td>{{ $u->colegiatura ?? '—' }}</td><td>{{ $u->ultimo_acceso_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td><span class="{{ $u->activo ? 'badge-green' : 'badge-red' }}">{{ $u->activo ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="text-right">@unless ($u->esTutor())<a href="{{ route('usuarios.edit', $u) }}" class="btn-secondary btn-sm">Editar</a>@endunless</td></tr>
        @endforeach
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $usuarios->links() }}</div>
</div>
@endsection
