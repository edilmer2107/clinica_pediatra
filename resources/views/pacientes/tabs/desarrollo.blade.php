<div class="grid gap-4 lg:grid-cols-3">
    <form method="POST" action="{{ route('pacientes.hitos.store', $paciente) }}" class="card lg:col-span-2">
        @csrf
        <div class="card-header"><h3 class="card-title">Hitos del desarrollo psicomotor ({{ $paciente->edadEnMeses() }} meses)</h3></div>
        <div class="card-body grid gap-4 md:grid-cols-2">
            @forelse ($hitos as $dominio => $lista)
                <div>
                    <h4 class="mb-2 text-sm font-semibold text-brand-700">{{ \App\Models\Milestone::DOMINIOS[$dominio] }}</h4>
                    <ul class="space-y-2 text-sm">
                        @foreach ($lista as $h)
                            <li class="rounded-lg border border-slate-200 p-2 {{ $h['evaluacion']?->alerta ? 'border-red-300 bg-red-50' : '' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span>{{ $h['hito']->descripcion }} <span class="text-xs text-slate-500">({{ $h['hito']->edad_esperada_meses }}-{{ $h['hito']->edad_limite_meses }} m)</span></span>
                                    @can('ficha.editar')
                                    <select name="hitos[{{ $h['hito']->id }}]" class="form-input w-28 py-1 text-xs">
                                        <option value="">—</option>
                                        <option value="1" @selected($h['evaluacion']?->logrado === true)>Logrado</option>
                                        <option value="0" @selected($h['evaluacion']?->logrado === false)>No logrado</option>
                                    </select>
                                    @endcan
                                </div>
                                @if ($h['evaluacion'])<div class="text-xs {{ $h['evaluacion']->alerta ? 'font-semibold text-red-700' : 'text-slate-500' }}">Última evaluación {{ $h['evaluacion']->fecha->format('d/m/Y') }}: {{ $h['evaluacion']->logrado ? 'logrado' : 'no logrado' }}{{ $h['evaluacion']->alerta ? ' — ALERTA' : '' }}</div>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-sm text-slate-500">No hay hitos aplicables a la edad del paciente.</p>
            @endforelse
        </div>
        @can('ficha.editar')<div class="flex justify-end border-t border-slate-200 px-5 py-3"><button class="btn-primary">Guardar evaluación</button></div>@endcan
    </form>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Cribado PSC-35</h3>
            @can('ficha.editar')<a href="{{ route('pacientes.psc35.create', $paciente) }}" class="btn-primary btn-sm">Aplicar</a>@endcan</div>
        <ul class="divide-y divide-slate-100 text-sm">
            @forelse ($paciente->psc35Screenings->sortByDesc('fecha') as $s)
                <li class="px-5 py-3">
                    <div class="flex justify-between"><span>{{ $s->fecha->format('d/m/Y') }}</span><span class="{{ $s->positivo ? 'badge-red' : 'badge-green' }}">{{ $s->positivo ? 'Positivo' : 'Negativo' }} · {{ $s->puntaje_total }}</span></div>
                    <div class="text-xs text-slate-500">Atención {{ $s->puntaje_atencion }} · Internalización {{ $s->puntaje_internalizacion }} · Externalización {{ $s->puntaje_externalizacion }}</div>
                    <div class="text-xs">{{ $s->interpretacion }}</div>
                </li>
            @empty
                <li class="px-5 py-4 text-slate-500">Sin cribados registrados. Aplica de 4 a 16 años.</li>
            @endforelse
        </ul>
    </div>
</div>
