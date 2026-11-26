@extends('layouts.app')
@section('title', $medicamento->exists ? 'Editar medicamento' : 'Nuevo medicamento')
@section('header', $medicamento->exists ? 'Editar medicamento' : 'Nuevo medicamento')

@section('content')
<form method="POST" action="{{ $medicamento->exists ? route('medicamentos.update', $medicamento) : route('medicamentos.store') }}" class="card" x-data="{ controlado: {{ old('es_controlado', $medicamento->es_controlado) ? 'true' : 'false' }} }">
    @csrf
    @if ($medicamento->exists) @method('PUT') @endif
    <div class="card-body grid gap-4 md:grid-cols-3">
        @foreach ([
            ['codigo', 'Código', 'text', true], ['nombre', 'Nombre comercial / genérico', 'text', true], ['principio_activo', 'Principio activo', 'text', true],
            ['concentracion', 'Concentración (ej. 250 mg/5 mL)', 'text', false], ['forma_farmaceutica', 'Forma farmacéutica', 'text', true], ['presentacion', 'Presentación', 'text', false],
            ['unidad', 'Unidad de dispensación (frasco, tableta...)', 'text', true], ['mg_por_unidad', 'mg por unidad', 'number', false], ['mg_por_ml', 'mg por mL (líquidos)', 'number', false],
            ['dosis_min_mg_kg_dia', 'Dosis mínima (mg/kg/día)', 'number', false], ['dosis_max_mg_kg_dia', 'Dosis máxima (mg/kg/día)', 'number', false], ['dosis_max_diaria_mg', 'Dosis máxima absoluta (mg/día)', 'number', false],
            ['dosis_min_mg_m2_dia', 'Dosis mínima (mg/m²/día)', 'number', false], ['dosis_max_mg_m2_dia', 'Dosis máxima (mg/m²/día)', 'number', false], ['precio_venta', 'Precio de venta (S/)', 'number', true],
            ['stock_minimo', 'Stock mínimo', 'number', true],
        ] as [$campo, $etiqueta, $tipo, $req])
            <div>
                <label class="form-label" for="{{ $campo }}">{{ $etiqueta }} @if($req)<span class="text-red-600">*</span>@endif</label>
                <input id="{{ $campo }}" name="{{ $campo }}" type="{{ $tipo }}" @if($tipo === 'number') step="any" min="0" @endif value="{{ old($campo, $medicamento->$campo) }}" class="form-input" @required($req)>
            </div>
        @endforeach
        <div class="flex flex-col gap-2 pt-6">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="es_controlado" value="1" x-model="controlado" @checked(old('es_controlado', $medicamento->es_controlado))> Medicamento controlado</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="es_vacuna" value="1" @checked(old('es_vacuna', $medicamento->es_vacuna))> Es vacuna</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="activo" value="1" @checked(old('activo', $medicamento->activo))> Activo</label>
        </div>
        <div x-show="controlado">
            <label class="form-label" for="tipo_control">Tipo de control</label>
            <select id="tipo_control" name="tipo_control" class="form-input">
                <option value="">—</option>
                <option value="estupefaciente" @selected(old('tipo_control', $medicamento->tipo_control) === 'estupefaciente')>Estupefaciente</option>
                <option value="psicotropico" @selected(old('tipo_control', $medicamento->tipo_control) === 'psicotropico')>Psicotrópico</option>
            </select>
        </div>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
        <a href="{{ route('medicamentos.index') }}" class="btn-secondary">Cancelar</a>
        <button class="btn-primary">Guardar</button>
    </div>
</form>
@endsection
