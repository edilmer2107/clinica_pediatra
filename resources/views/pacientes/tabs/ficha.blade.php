@php($f = $paciente->pediatricRecord)
<div class="card">
    <div class="card-header"><h3 class="card-title">Ficha clínica pediátrica (antecedentes)</h3>
        @can('ficha.editar')<a href="{{ route('pacientes.ficha.edit', $paciente) }}" class="btn-primary btn-sm">{{ $f ? 'Editar ficha' : 'Completar ficha' }}</a>@endcan
    </div>
    @if (! $f)
        <div class="card-body"><div class="alert-warning">La ficha clínica aún no ha sido completada.</div></div>
    @else
        <div class="card-body grid gap-6 md:grid-cols-3 text-sm">
            <div><h4 class="mb-2 font-semibold text-brand-700">Prenatales</h4><dl class="space-y-1">
                <div><dt class="inline text-slate-500">Controles prenatales:</dt> <dd class="inline">{{ $f->numero_controles_prenatales }}</dd></div>
                <div><dt class="inline text-slate-500">Edad materna:</dt> <dd class="inline">{{ $f->edad_materna }} años</dd></div>
                <div><dt class="inline text-slate-500">Hierro/ácido fólico:</dt> <dd class="inline">{{ $f->suplemento_hierro_acido_folico ? 'Sí' : 'No' }}</dd></div>
                <div><dt class="text-slate-500">Patologías del embarazo:</dt> <dd>{{ $f->patologias_embarazo }}</dd></div></dl></div>
            <div><h4 class="mb-2 font-semibold text-brand-700">Natales</h4><dl class="space-y-1">
                <div><dt class="inline text-slate-500">Edad gestacional:</dt> <dd class="inline">{{ $f->edad_gestacional_semanas }} sem {{ $f->edad_gestacional_semanas < 37 ? '(pretérmino)' : '' }}</dd></div>
                <div><dt class="inline text-slate-500">Parto:</dt> <dd class="inline">{{ ['eutocico' => 'Eutócico', 'cesarea' => 'Cesárea', 'instrumentado' => 'Instrumentado'][$f->tipo_parto] }}</dd></div>
                <div><dt class="inline text-slate-500">Peso / talla al nacer:</dt> <dd class="inline">{{ $f->peso_nacer_kg }} kg / {{ $f->talla_nacer_cm }} cm</dd></div>
                <div><dt class="inline text-slate-500">PC al nacer:</dt> <dd class="inline">{{ $f->perimetro_cefalico_nacer_cm ?? '—' }} cm</dd></div>
                <div><dt class="inline text-slate-500">APGAR:</dt> <dd class="inline">{{ $f->apgar_1 }} / {{ $f->apgar_5 }}</dd></div>
                <div><dt class="text-slate-500">Complicaciones:</dt> <dd>{{ $f->complicaciones_parto }}</dd></div></dl></div>
            <div><h4 class="mb-2 font-semibold text-brand-700">Posnatales</h4><dl class="space-y-1">
                <div><dt class="inline text-slate-500">Lactancia:</dt> <dd class="inline">{{ ucfirst($f->lactancia) }}</dd></div>
                <div><dt class="inline text-slate-500">Tamizaje neonatal:</dt> <dd class="inline">{{ $f->tamizaje_neonatal ? 'Sí' : 'No' }}</dd></div>
                <div><dt class="text-slate-500">Hospitalizaciones:</dt> <dd>{{ $f->hospitalizaciones }}</dd></div>
                <div><dt class="text-slate-500">Enfermedades previas:</dt> <dd>{{ $f->enfermedades_previas }}</dd></div>
                <div><dt class="text-slate-500">Antecedentes familiares:</dt> <dd>{{ $f->antecedentes_familiares }}</dd></div></dl></div>
        </div>
    @endif
</div>
