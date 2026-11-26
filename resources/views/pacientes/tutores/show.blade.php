@extends('layouts.app')
@section('title', $tutor->nombre_completo)
@section('header', 'Tutor · '.$tutor->nombre_completo)

@section('content')
<div class="grid gap-4 lg:grid-cols-2">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Datos del tutor</h2>@can('pacientes.gestionar')<a href="{{ route('tutores.edit', $tutor) }}" class="btn-secondary btn-sm">Editar</a>@endcan</div>
        <dl class="card-body grid grid-cols-2 gap-2 text-sm">
            <dt class="text-slate-500">DNI</dt><dd>{{ $tutor->dni }}</dd>
            <dt class="text-slate-500">Celular</dt><dd>{{ $tutor->telefono }}</dd>
            <dt class="text-slate-500">Correo</dt><dd>{{ $tutor->email ?? '—' }}</dd>
            <dt class="text-slate-500">Dirección</dt><dd>{{ $tutor->direccion ?? '—' }}</dd>
            <dt class="text-slate-500">Cuenta del portal</dt><dd>{{ $tutor->user ? $tutor->user->email : 'No creada' }}</dd>
        </dl>
        @if (! $tutor->user)
            @can('pacientes.gestionar')
            <form method="POST" action="{{ route('tutores.acceso', $tutor) }}" class="grid gap-3 border-t border-slate-200 p-5 md:grid-cols-3">
                @csrf
                <div class="md:col-span-3 text-sm font-semibold">Crear acceso al portal de tutores</div>
                <div><label class="form-label" for="a_email">Correo *</label><input id="a_email" type="email" name="email" value="{{ old('email', $tutor->email) }}" class="form-input" required></div>
                <div><label class="form-label" for="a_pass">Contraseña *</label><input id="a_pass" type="password" name="password" class="form-input" required autocomplete="new-password"></div>
                <div><label class="form-label" for="a_pass2">Confirmar *</label><input id="a_pass2" type="password" name="password_confirmation" class="form-input" required autocomplete="new-password"></div>
                <p class="form-help md:col-span-2">Mínimo 8 caracteres, con mayúsculas, minúsculas y números.</p>
                <div class="text-right"><button class="btn-primary btn-sm">Crear acceso</button></div>
            </form>
            @endcan
        @endif
    </div>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Pacientes a cargo</h2></div>
        <ul class="divide-y divide-slate-100 text-sm">
            @foreach ($tutor->patients as $p)
                <li class="flex justify-between px-5 py-3"><a href="{{ route('pacientes.show', $p) }}" class="text-brand-700 hover:underline">{{ $p->nombre_completo }} ({{ $p->historia_clinica }})</a><span class="text-xs text-slate-500">{{ $p->pivot->parentesco }}{{ $p->pivot->es_principal ? ' · principal' : '' }}</span></li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
