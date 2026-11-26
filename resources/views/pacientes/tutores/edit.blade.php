@extends('layouts.app')
@section('title', 'Editar tutor')
@section('header', 'Editar tutor')

@section('content')
<form method="POST" action="{{ route('tutores.update', $tutor) }}" class="card max-w-3xl">
    @csrf @method('PUT')
    <div class="card-body grid gap-4 md:grid-cols-2">
        @foreach (['dni' => 'DNI', 'nombres' => 'Nombres', 'apellidos' => 'Apellidos', 'telefono' => 'Celular', 'email' => 'Correo', 'direccion' => 'Dirección'] as $campo => $et)
            <div><label class="form-label" for="{{ $campo }}">{{ $et }}</label><input id="{{ $campo }}" name="{{ $campo }}" value="{{ old($campo, $tutor->$campo) }}" class="form-input" @required(! in_array($campo, ['email', 'direccion']))></div>
        @endforeach
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('tutores.show', $tutor) }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Guardar</button></div>
</form>
@endsection
