@extends('layouts.app')
@section('title', 'Editar paciente')
@section('header', 'Editar paciente '.$paciente->historia_clinica)

@section('content')
<form method="POST" action="{{ route('pacientes.update', $paciente) }}" class="card">
    @csrf @method('PUT')
    <div class="card-body">@include('pacientes._form_paciente')</div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('pacientes.show', $paciente) }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Guardar cambios</button></div>
</form>
@endsection
