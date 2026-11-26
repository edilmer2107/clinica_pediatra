@extends('layouts.app')
@section('title', 'Ficha ESAVI')
@section('header', 'Ficha de notificación ESAVI #'.$esavi->id)

@section('content')
<div class="card max-w-4xl">
    <div class="card-header"><h2 class="card-title">Notificación de Evento Supuestamente Atribuido a la Vacunación o Inmunización</h2><button onclick="window.print()" class="btn-secondary btn-sm no-print">Imprimir / Guardar PDF</button></div>
    <div class="card-body grid gap-6 text-sm md:grid-cols-2">
        <div><h3 class="mb-2 font-semibold text-brand-700">Datos del paciente</h3>
            <p>{{ $esavi->patient->nombre_completo }} · {{ $esavi->patient->historia_clinica }}</p>
            <p>DNI {{ $esavi->patient->dni ?? '—' }} · {{ $esavi->patient->sexo === 'M' ? 'Masculino' : 'Femenino' }} · Nac. {{ $esavi->patient->fecha_nacimiento->format('d/m/Y') }}</p>
            <p>Tutor: {{ $esavi->patient->tutorPrincipal()?->nombre_completo }} · {{ $esavi->patient->tutorPrincipal()?->telefono }}</p></div>
        <div><h3 class="mb-2 font-semibold text-brand-700">Vacuna y lote</h3>
            <p>{{ $esavi->immunization->vaccine->nombre }} — {{ $esavi->immunization->scheduleDose?->nombre_dosis }}</p>
            <p>Lote: <strong>{{ $esavi->immunization->lot->numero_lote }}</strong> · Vence {{ $esavi->immunization->lot->fecha_vencimiento->format('d/m/Y') }}</p>
            <p>Aplicada: {{ $esavi->immunization->fecha_aplicacion->format('d/m/Y') }} · {{ $esavi->immunization->sitio_aplicacion }} · por {{ $esavi->immunization->aplicadoPor?->name }}</p></div>
        <div class="md:col-span-2"><h3 class="mb-2 font-semibold text-brand-700">Evento</h3>
            <p>Inicio de síntomas: {{ $esavi->fecha_inicio_sintomas->format('d/m/Y H:i') }} · Gravedad: <strong>{{ ucfirst($esavi->gravedad) }}</strong> · Desenlace: {{ str_replace('_', ' ', $esavi->desenlace) }}</p>
            <p class="mt-2"><strong>Síntomas:</strong> {{ $esavi->sintomas }}</p>
            <p><strong>Descripción:</strong> {{ $esavi->descripcion ?? '—' }}</p>
            <p><strong>Tratamiento:</strong> {{ $esavi->tratamiento ?? '—' }}</p></div>
        <div class="md:col-span-2 text-xs text-slate-500">Notificado el {{ $esavi->notificado_at?->format('d/m/Y H:i') }} por {{ $esavi->reportadoPor?->name }}. Documento generado por el sistema para notificación a la autoridad sanitaria.</div>
    </div>
</div>
@endsection
