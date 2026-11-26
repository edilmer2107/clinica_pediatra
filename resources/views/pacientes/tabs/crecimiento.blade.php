<div class="grid gap-4 lg:grid-cols-3">
    @can('mediciones.registrar')
    <form method="POST" action="{{ route('pacientes.mediciones.store', $paciente) }}" class="card">
        @csrf
        <div class="card-header"><h3 class="card-title">Registrar medición</h3></div>
        <div class="card-body space-y-3">
            <div><label class="form-label" for="m_fecha">Fecha *</label><input id="m_fecha" type="date" name="fecha" value="{{ old('fecha', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-input" required></div>
            <div><label class="form-label" for="m_peso">Peso (kg) *</label><input id="m_peso" type="number" step="0.01" min="0.4" max="150" name="peso_kg" value="{{ old('peso_kg') }}" class="form-input" required></div>
            <div><label class="form-label" for="m_talla">Talla / longitud (cm) *</label><input id="m_talla" type="number" step="0.1" min="25" max="220" name="talla_cm" value="{{ old('talla_cm') }}" class="form-input" required></div>
            <div><label class="form-label" for="m_pc">Perímetro cefálico (cm)</label><input id="m_pc" type="number" step="0.1" min="20" max="65" name="perimetro_cefalico_cm" value="{{ old('perimetro_cefalico_cm') }}" class="form-input"></div>
            <p class="form-help">El sistema calcula automáticamente Z y percentil OMS para la edad exacta y genera alertas.</p>
            <button class="btn-primary w-full">Calcular y guardar</button>
        </div>
    </form>
    @endcan
    <div class="card {{ auth()->user()->can('mediciones.registrar') ? 'lg:col-span-2' : 'lg:col-span-3' }}">
        <div class="card-header"><h3 class="card-title">Historial de mediciones</h3></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Fecha</th><th>Edad</th><th>Peso</th><th>P/E</th><th>Talla</th><th>T/E</th><th>PC</th><th>PC/E</th><th>IMC</th><th>IMC/E</th><th>Alertas</th></tr></thead>
                <tbody>
                @forelse ($paciente->measurements->sortByDesc('fecha') as $m)
                    @php($z = fn ($v) => $v === null ? '—' : (abs($v) > 2 ? '<span class="font-semibold text-red-600">' : '<span>').'Z '.number_format($v, 2).'</span>')
                    <tr>
                        <td>{{ $m->fecha->format('d/m/Y') }}</td>
                        <td class="text-xs">{{ intdiv($m->edad_dias, 365) }}a {{ intdiv($m->edad_dias % 365, 30) }}m</td>
                        <td>{{ $m->peso_kg }} kg</td><td class="text-xs">{!! $z($m->z_peso_edad) !!}<br>{{ $m->p_peso_edad !== null ? 'P'.$m->p_peso_edad : '' }}</td>
                        <td>{{ $m->talla_cm }} cm</td><td class="text-xs">{!! $z($m->z_talla_edad) !!}<br>{{ $m->p_talla_edad !== null ? 'P'.$m->p_talla_edad : '' }}</td>
                        <td>{{ $m->perimetro_cefalico_cm ? $m->perimetro_cefalico_cm.' cm' : '—' }}</td><td class="text-xs">{!! $z($m->z_pc_edad) !!}<br>{{ $m->p_pc_edad !== null ? 'P'.$m->p_pc_edad : '' }}</td>
                        <td>{{ $m->imc }}</td><td class="text-xs">{!! $z($m->z_imc_edad) !!}</td>
                        <td class="text-xs">@foreach ($m->alertas ?? [] as $a)<div class="{{ $a['tipo'] === 'info' ? 'text-slate-500' : 'font-medium text-red-700' }}">{{ $a['mensaje'] }}</div>@endforeach</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="py-4 text-center text-slate-500">Sin mediciones registradas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($paciente->edadEnDias() <= \App\Services\Pacientes\PercentilOmsService::EDAD_MAXIMA_DIAS)
<div class="mt-4 grid gap-4 lg:grid-cols-2">
    @foreach ($graficos as $clave => $g)
        <div class="card"><div class="card-header"><h3 class="card-title">{{ $g['titulo'] }} ({{ $paciente->sexo === 'M' ? 'niños' : 'niñas' }}, OMS)</h3></div><div class="card-body"><canvas id="graf_{{ $clave }}"></canvas></div></div>
    @endforeach
</div>
@else
    <div class="alert-info mt-4">Las curvas OMS 0-5 años no aplican a la edad actual del paciente.</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const graficos = @json($graficos);
    const estilos = { P3: '#e4572e', P15: '#f3a712', P50: '#17a589', P85: '#f3a712', P97: '#e4572e' };
    for (const [clave, g] of Object.entries(graficos)) {
        const canvas = document.getElementById('graf_' + clave);
        if (!canvas) continue;
        const datasets = Object.entries(g.curvas).map(([n, d]) => ({ label: n, data: d, borderColor: estilos[n], borderWidth: n === 'P50' ? 2 : 1, pointRadius: 0, borderDash: n === 'P50' ? [] : [4, 3], fill: false }));
        datasets.push({ label: 'Paciente', data: g.puntos, borderColor: '#1f6fc0', backgroundColor: '#1f6fc0', pointRadius: 4, borderWidth: 2, showLine: true });
        new window.Chart(canvas, { type: 'line', data: { datasets }, options: { parsing: false, aspectRatio: 1.6, scales: { x: { type: 'linear', title: { display: true, text: 'Edad (meses)' } }, y: { title: { display: true, text: g.etiqueta } } }, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } } });
    }
});
</script>
@endpush
