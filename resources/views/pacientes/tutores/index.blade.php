@extends('layouts.app')
@section('title', 'Tutores')
@section('header', 'Tutores')

@section('content')
<div class="card">
    <div class="card-header"><form method="GET" class="flex gap-2"><input type="search" name="buscar" value="{{ $buscar }}" placeholder="DNI o nombre" class="form-input w-72"><button class="btn-secondary">Buscar</button></form></div>
    <table class="table">
        <thead><tr><th>DNI</th><th>Tutor</th><th>Celular</th><th>Correo</th><th>Pacientes</th><th>Portal</th><th></th></tr></thead>
        <tbody>
        @forelse ($tutores as $t)
            <tr><td>{{ $t->dni }}</td><td class="font-medium">{{ $t->apellidos }}, {{ $t->nombres }}</td><td>{{ $t->telefono }}</td><td>{{ $t->email ?? '—' }}</td><td>{{ $t->patients_count }}</td>
                <td>{!! $t->user ? '<span class="badge-green">Activo</span>' : '<span class="badge-gray">Sin cuenta</span>' !!}</td>
                <td class="text-right"><a href="{{ route('tutores.show', $t) }}" class="btn-secondary btn-sm">Ver</a></td></tr>
        @empty
            <tr><td colspan="7" class="py-6 text-center text-slate-500">Sin tutores.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-5 py-3">{{ $tutores->links() }}</div>
</div>
@endsection
