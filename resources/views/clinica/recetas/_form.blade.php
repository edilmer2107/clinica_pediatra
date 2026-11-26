@php($ultima = $ultimaMedicion)
<div class="card" x-data="receta()">
    <div class="card-header"><h3 class="card-title">Nueva receta electrónica</h3><span class="text-xs text-slate-500">Cálculo por peso (mg/kg/día) o superficie corporal (mg/m²/día)</span></div>
    <form method="POST" action="{{ route('recetas.store', $consulta) }}" class="card-body space-y-4" @submit="if (!confirmar()) $event.preventDefault()">
        @csrf
        <div class="grid gap-3 md:grid-cols-3">
            <div><label class="form-label" for="r_peso">Peso (kg) *</label><input id="r_peso" type="number" step="0.01" name="peso_kg" x-model="peso" class="form-input" required></div>
            <div><label class="form-label" for="r_talla">Talla (cm)</label><input id="r_talla" type="number" step="0.1" name="talla_cm" x-model="talla" class="form-input"></div>
            <div class="flex items-end"><button type="button" class="btn-secondary w-full" @click="calcular()">Calcular dosis y validar</button></div>
        </div>
        <template x-for="(it, i) in items" :key="i">
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="grid gap-2 md:grid-cols-6">
                    <select :name="`items[${i}][medication_id]`" x-model="it.medication_id" class="form-input md:col-span-2" required aria-label="Medicamento">
                        <option value="">Medicamento…</option>
                        @foreach ($medicamentos as $m)<option value="{{ $m->id }}">{{ $m->nombre }} {{ $m->concentracion }} @if ($m->dosis_max_mg_kg_dia)({{ (float) $m->dosis_min_mg_kg_dia }}–{{ (float) $m->dosis_max_mg_kg_dia }} mg/kg/d)@endif</option>@endforeach
                    </select>
                    <select :name="`items[${i}][metodo_calculo]`" x-model="it.metodo_calculo" class="form-input" aria-label="Método"><option value="peso">mg/kg/día</option><option value="sc">mg/m²/día</option></select>
                    <input type="number" step="any" :name="`items[${i}][dosis_indicada]`" x-model="it.dosis_indicada" placeholder="Dosis" class="form-input" required aria-label="Dosis indicada">
                    <select :name="`items[${i}][frecuencia_horas]`" x-model="it.frecuencia_horas" class="form-input" aria-label="Frecuencia"><option value="4">c/4 h</option><option value="6">c/6 h</option><option value="8">c/8 h</option><option value="12">c/12 h</option><option value="24">c/24 h</option></select>
                    <input type="number" min="1" max="90" :name="`items[${i}][duracion_dias]`" x-model="it.duracion_dias" placeholder="Días" class="form-input" required aria-label="Duración en días">
                    <select :name="`items[${i}][via]`" x-model="it.via" class="form-input" aria-label="Vía"><option value="oral">Oral</option><option value="IM">IM</option><option value="IV">IV</option><option value="SC">SC</option><option value="inhalatoria">Inhalatoria</option><option value="tópica">Tópica</option><option value="rectal">Rectal</option></select>
                    <input :name="`items[${i}][indicaciones]`" x-model="it.indicaciones" placeholder="Indicaciones (ej. después de comidas)" class="form-input md:col-span-3" aria-label="Indicaciones">
                    <input :name="`items[${i}][justificacion]`" x-model="it.justificacion" placeholder="Justificación (obligatoria si la dosis está fuera de rango)" class="form-input md:col-span-2" aria-label="Justificación">
                    <button type="button" class="btn-secondary btn-sm" @click="items.splice(i, 1)" x-show="items.length > 1">Quitar</button>
                </div>
                <template x-if="resultado && resultado.lineas[i]">
                    <div class="mt-2 text-xs" :class="resultado.lineas[i].alerta_dosis ? 'text-red-700' : 'text-emerald-700'">
                        <span x-text="`Dosis diaria: ${resultado.lineas[i].dosis_diaria_mg} mg · por toma: ${resultado.lineas[i].dosis_por_toma_mg} mg` + (resultado.lineas[i].volumen_por_toma_ml ? ` (${resultado.lineas[i].volumen_por_toma_ml} mL)` : '') + ` · ${resultado.lineas[i].tomas_dia} tomas/día · dispensar ${resultado.lineas[i].cantidad} unidad(es)`"></span>
                        <template x-for="a in resultado.lineas[i].alertas"><div class="font-semibold" x-text="'⚠ ' + a"></div></template>
                    </div>
                </template>
            </div>
        </template>
        <button type="button" class="btn-secondary btn-sm" @click="items.push(nuevo())">+ Agregar medicamento</button>
        <template x-if="resultado && resultado.interacciones.length">
            <div class="alert-error"><strong>Interacciones medicamentosas:</strong>
                <template x-for="x in resultado.interacciones"><div x-text="`${x.a} + ${x.b} (${x.severidad.toUpperCase()}): ${x.descripcion}`"></div></template>
                <textarea name="justificacion_interaccion" rows="2" class="form-input mt-2" placeholder="Justificación clínica (obligatoria para interacciones graves)"></textarea>
            </div>
        </template>
        <div class="alert-error" x-show="error" x-text="error"></div>
        <div><label class="form-label" for="r_obs">Observaciones</label><input id="r_obs" name="observaciones" class="form-input"></div>
        <div class="text-right"><button class="btn-primary">Emitir receta</button></div>
    </form>
</div>
@push('scripts')
<script>
function receta() {
    return {
        peso: '{{ $ultima?->peso_kg }}', talla: '{{ $ultima?->talla_cm }}', items: [], resultado: null, error: '',
        nuevo() { return { medication_id: '', metodo_calculo: 'peso', dosis_indicada: '', frecuencia_horas: '8', duracion_dias: 7, via: 'oral', indicaciones: '', justificacion: '' }; },
        init() { this.items.push(this.nuevo()); },
        async calcular() {
            this.error = ''; this.resultado = null;
            const r = await fetch('{{ route('recetas.calcular', $consulta) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ peso_kg: this.peso, talla_cm: this.talla || null, items: this.items }) });
            const j = await r.json();
            if (!r.ok) { this.error = j.message || 'Revise los datos de la receta.'; return; }
            this.resultado = j;
        },
        confirmar() {
            if (!this.resultado) return confirm('No ha validado la dosis con "Calcular dosis y validar". ¿Emitir de todas formas?');
            return true;
        }
    };
}
</script>
@endpush
