@extends('layouts.app')
@section('title', 'Ingresar lote')
@section('header', 'Farmacia · Ingreso de lote')

@section('content')
<form method="POST" action="{{ route('lotes.store') }}" class="card max-w-3xl">
    @csrf
    <div class="card-body grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="form-label" for="medication_id">Medicamento <span class="text-red-600">*</span></label>
            <select id="medication_id" name="medication_id" class="form-input" required>
                <option value="">Seleccione…</option>
                @foreach ($medicamentos as $m)
                    <option value="{{ $m->id }}" @selected(old('medication_id', $medicamentoId) == $m->id)>{{ $m->nombre }} {{ $m->concentracion }} ({{ $m->forma_farmaceutica }}){{ $m->es_controlado ? ' — CONTROLADO' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div><label class="form-label" for="numero_lote">N° de lote *</label><input id="numero_lote" name="numero_lote" value="{{ old('numero_lote') }}" class="form-input" required></div>
        <div><label class="form-label" for="fecha_vencimiento">Fecha de vencimiento *</label><input id="fecha_vencimiento" type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}" class="form-input" required></div>
        <div><label class="form-label" for="cantidad">Cantidad *</label><input id="cantidad" type="number" min="1" name="cantidad" value="{{ old('cantidad') }}" class="form-input" required></div>
        <div><label class="form-label" for="costo_unitario">Costo unitario (S/) *</label><input id="costo_unitario" type="number" step="0.01" min="0" name="costo_unitario" value="{{ old('costo_unitario', 0) }}" class="form-input" required></div>
        <div><label class="form-label" for="almacen">Almacén *</label><input id="almacen" name="almacen" value="{{ old('almacen', 'Almacén principal') }}" class="form-input" required></div>
        <div><label class="form-label" for="ubicacion">Ubicación (estante/anaquel) *</label><input id="ubicacion" name="ubicacion" value="{{ old('ubicacion') }}" class="form-input" placeholder="Estante A-3" required></div>
        <div><label class="form-label" for="proveedor">Proveedor</label><input id="proveedor" name="proveedor" value="{{ old('proveedor') }}" class="form-input"></div>
        <div><label class="form-label" for="documento">Documento de ingreso (guía/factura)</label><input id="documento" name="documento" value="{{ old('documento') }}" class="form-input"></div>
    </div>
    <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
        <a href="{{ url()->previous() }}" class="btn-secondary">Cancelar</a>
        <button class="btn-primary">Registrar ingreso</button>
    </div>
</form>
@endsection
