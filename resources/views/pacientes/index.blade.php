@extends('layouts.app')
@section('title', 'Pacientes')
@section('header', 'Pacientes pediátricos')

@section('content')
<div class="card">
    <div class="card-header">
        <form method="GET" class="flex gap-2"><input type="search" name="buscar" value="{{ $buscar }}" placeholder="Nombre, HC, DNI del paciente o tutor" class="form-input w-80"><button class="btn-secondary">Buscar</button></form>
        @can('pacientes.gestionar')<a href="{{ route('pacientes.create') }}" class="btn-primary">Registrar paciente</a>@endcan
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>HC</th><th>Paciente</th><th>DNI</th><th>Edad</th><th>Sexo</th><th>Tutor principal</th><th>Seguro</th><th></th></tr></thead>
            <tbody>
            @forelse ($pacientes as $p)
                <tr>
                    <td class="font-mono text-xs">{{ $p->historia_clinica }}</td>
                    <td class="font-medium">{{ $p->apellidos }}, {{ $p->nombres }}</td>
                    <td>{{ $p->dni ?? '—' }}</td>
                    <td>{{ $p->edad_texto }}</td>
                    <td>{{ $p->sexo === 'M' ? 'Masculino' : 'Femenino' }}</td>
                    <td class="text-xs">@if ($t = $p->tutors->first()){{ $t->nombre_completo }} ({{ $t->pivot->parentesco }})<br>{{ $t->telefono }}@else — @endif</td>
                    <td>{{ $p->seguro ?? '—' }}</td>
                    <td class="text-right"><a href="{{ route('pacientes.show', $p) }}" class="btn-secondary btn-sm">Abrir</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="py-6 text-center text-slate-500">No se encontraron pacientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3">{{ $pacientes->links() }}</div>
</div>
@endsection
