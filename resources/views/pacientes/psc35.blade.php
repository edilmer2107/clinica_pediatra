@extends('layouts.app')
@section('title', 'PSC-35')
@section('header', 'Cribado PSC-35 · '.$paciente->nombre_completo)

@section('content')
<form method="POST" action="{{ route('pacientes.psc35.store', $paciente) }}" class="card">
    @csrf
    <div class="card-header"><h2 class="card-title">Lista de Síntomas Pediátricos (PSC-35)</h2><span class="text-xs text-slate-500">Punto de corte para la edad: ≥ {{ $corte }}</span></div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>#</th><th>Ítem</th><th class="text-center">Nunca (0)</th><th class="text-center">Algunas veces (1)</th><th class="text-center">Frecuentemente (2)</th></tr></thead>
            <tbody>
            @foreach ($items as $n => $texto)
                <tr>
                    <td>{{ $n }}</td><td>{{ $texto }}</td>
                    @foreach ([0, 1, 2] as $valor)
                        <td class="text-center"><input type="radio" name="respuestas[{{ $n }}]" value="{{ $valor }}" @checked((string) old("respuestas.$n") === (string) $valor) required aria-label="Ítem {{ $n }} valor {{ $valor }}"></td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3"><a href="{{ route('pacientes.show', [$paciente, 'tab' => 'desarrollo']) }}" class="btn-secondary">Cancelar</a><button class="btn-primary">Calcular y guardar</button></div>
</form>
@endsection
