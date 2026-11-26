@extends('layouts.app')
@section('title', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')
@section('header', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('content')
<form method="POST" action="{{ $usuario->exists ? route('usuarios.update', $usuario) : route('usuarios.store') }}" class="card max-w-3xl">
    @csrf @if ($usuario->exists) @method('PUT') @endif
    <div class="card-body grid gap-4 md:grid-cols-2">
        <div><label class="form-label" for="name">Nombre completo *</label><input id="name" name="name" value="{{ old('name', $usuario->name) }}" class="form-input" required></div>
        <div><label class="form-label" for="email">Correo *</label><input id="email" type="email" name="email" value="{{ old('email', $usuario->email) }}" class="form-input" required></div>
        <div><label class="form-label" for="dni">DNI</label><input id="dni" name="dni" value="{{ old('dni', $usuario->dni) }}" class="form-input" maxlength="8"></div>
        <div><label class="form-label" for="telefono">Celular</label><input id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono) }}" class="form-input" maxlength="9"></div>
        <div><label class="form-label" for="rol">Rol *</label><select id="rol" name="rol" class="form-input" required>@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected(old('rol', $usuario->getRoleNames()->first()) === $k)>{{ $v }}</option>@endforeach</select></div>
        <div><label class="form-label" for="especialidad">Especialidad</label><input id="especialidad" name="especialidad" value="{{ old('especialidad', $usuario->especialidad) }}" class="form-input"></div>
        <div><label class="form-label" for="colegiatura">Colegiatura (CMP/CQFP/CEP)</label><input id="colegiatura" name="colegiatura" value="{{ old('colegiatura', $usuario->colegiatura) }}" class="form-input"></div>
        <label class="flex items-center gap-2 pt-6 text-sm"><input type="checkbox" name="activo" value="1" @checked(old('activo', $usuario->activo))> Cuenta activa</label>
        <div><label class="form-label" for="password">Contraseña {{ $usuario->exists ? '(dejar vacío para no cambiar)' : '*' }}</label><input id="password" type="password" name="password" class="form-input" autocomplete="new-password" @required(! $usuario->exists)></div>
        <div><label class="form-label" for="password_confirmation">Confirmar contraseña</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-input" autocomplete="new-password"></div>
        <p class="form-help md:col-span-2">La contraseña debe tener al menos 8 caracteres, con mayúsculas, minúsculas y números.</p>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('usuarios.index') }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Guardar</button></div>
</form>
@endsection
