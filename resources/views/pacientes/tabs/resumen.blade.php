<div class="grid gap-4 lg:grid-cols-2">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Tutores vinculados</h3></div>
        <ul class="divide-y divide-slate-100">
            @foreach ($paciente->tutors as $t)
                <li class="flex items-start justify-between gap-3 px-5 py-3 text-sm">
                    <div>
                        <a href="{{ route('tutores.show', $t) }}" class="font-medium text-brand-700 hover:underline">{{ $t->nombre_completo }}</a>
                        @if ($t->pivot->es_principal)<span class="badge-blue">Principal</span>@endif
                        <div class="text-xs text-slate-500">{{ $t->pivot->parentesco }} · DNI {{ $t->dni }} · {{ $t->telefono }} · {{ $t->email ?? 'sin correo' }}</div>
                        <div class="text-xs text-slate-500">{{ $t->pivot->puede_retirar_medicamentos ? 'Autorizado a retirar medicamentos' : 'No autorizado a retirar medicamentos' }}</div>
                    </div>
                    @can('pacientes.gestionar')
                        <form method="POST" action="{{ route('pacientes.tutores.desvincular', [$paciente, $t]) }}" onsubmit="return confirm('¿Desvincular a este tutor?')">@csrf @method('DELETE')<button class="btn-secondary btn-sm">Desvincular</button></form>
                    @endcan
                </li>
            @endforeach
        </ul>
        @can('pacientes.gestionar')
        <form method="POST" action="{{ route('pacientes.tutores.vincular', $paciente) }}" class="grid gap-3 border-t border-slate-200 p-5 md:grid-cols-3" x-data="{ abierto: false }">
            @csrf
            <div class="md:col-span-3"><button type="button" class="btn-secondary btn-sm" @click="abierto = !abierto">+ Vincular otro tutor</button></div>
            <template x-if="abierto"><div class="contents">
                <div><label class="form-label">DNI *</label><input name="dni" class="form-input" required maxlength="8"></div>
                <div><label class="form-label">Nombres</label><input name="nombres" class="form-input"></div>
                <div><label class="form-label">Apellidos</label><input name="apellidos" class="form-input"></div>
                <div><label class="form-label">Celular</label><input name="telefono" class="form-input" maxlength="9"></div>
                <div><label class="form-label">Correo</label><input name="email" type="email" class="form-input"></div>
                <div><label class="form-label">Parentesco *</label><select name="parentesco" class="form-input">@foreach (\App\Http\Controllers\Pacientes\PacienteController::PARENTESCOS as $p)<option>{{ $p }}</option>@endforeach</select></div>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="es_principal" value="0"><input type="checkbox" name="es_principal" value="1"> Tutor principal</label>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="puede_retirar_medicamentos" value="0"><input type="checkbox" name="puede_retirar_medicamentos" value="1" checked> Puede retirar medicamentos</label>
                <div class="text-right"><button class="btn-primary btn-sm">Vincular</button></div>
                <p class="form-help md:col-span-3">Si el DNI ya está registrado, solo se requiere el parentesco.</p>
            </div></template>
        </form>
        @endcan
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Consentimientos informados</h3></div>
        <table class="table">
            <thead><tr><th>Fecha</th><th>Tutor</th><th>Datos</th><th>Atención</th><th>Notif.</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse ($paciente->consents->sortByDesc('aceptado_at') as $c)
                <tr>
                    <td>{{ $c->aceptado_at?->format('d/m/Y H:i') }}</td><td class="text-xs">{{ $c->tutor->nombre_completo }}</td>
                    <td>{{ $c->acepta_tratamiento_datos ? '✔' : '✘' }}</td><td>{{ $c->acepta_atencion_medica ? '✔' : '✘' }}</td><td>{{ $c->acepta_notificaciones ? '✔' : '✘' }}</td>
                    <td>@if ($c->revocado_at)<span class="badge-red">Revocado</span>@else<span class="badge-green">Vigente v{{ $c->version }}</span>@endif</td>
                    <td>@if (! $c->revocado_at)@can('pacientes.gestionar')<form method="POST" action="{{ route('consentimientos.revocar', $c) }}" onsubmit="return confirm('¿Revocar el consentimiento?')">@csrf @method('PATCH')<button class="btn-secondary btn-sm">Revocar</button></form>@endcan @endif</td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-4 text-center text-slate-500">Sin consentimientos.</td></tr>
            @endforelse
            </tbody>
        </table>
        @can('pacientes.gestionar')
        <form method="POST" action="{{ route('pacientes.consentimientos.store', $paciente) }}" class="space-y-2 border-t border-slate-200 p-5 text-sm">
            @csrf
            <div class="flex items-center gap-2"><label for="c_tutor">Tutor:</label><select id="c_tutor" name="tutor_id" class="form-input w-64">@foreach ($paciente->tutors as $t)<option value="{{ $t->id }}">{{ $t->nombre_completo }}</option>@endforeach</select></div>
            <label class="flex items-center gap-2"><input type="checkbox" name="acepta_tratamiento_datos" value="1"> Tratamiento de datos (obligatorio)</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="acepta_atencion_medica" value="1" checked> Atención médica</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="acepta_notificaciones" value="1" checked> Notificaciones</label>
            <button class="btn-primary btn-sm">Registrar nuevo consentimiento</button>
        </form>
        @endcan
    </div>

    <div class="card lg:col-span-2">
        <div class="card-header"><h3 class="card-title">Datos generales</h3></div>
        <dl class="card-body grid grid-cols-2 gap-2 text-sm md:grid-cols-4">
            <dt class="text-slate-500">Grupo sanguíneo</dt><dd>{{ $paciente->grupo_sanguineo ?? '—' }}</dd>
            <dt class="text-slate-500">Dirección</dt><dd>{{ $paciente->direccion ?? '—' }}</dd>
            <dt class="text-slate-500">Registrado</dt><dd>{{ $paciente->created_at->format('d/m/Y') }}</dd>
            <dt class="text-slate-500">Última medición</dt><dd>{{ $paciente->measurements->last()?->fecha?->format('d/m/Y') ?? '—' }}</dd>
        </dl>
    </div>
</div>
