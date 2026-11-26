<div class="card">
    <div class="card-header">
        <h3 class="card-title">Calendario de vacunación (esquema regular)</h3>
        <div class="flex gap-2">
            @php($nVencidas = $calendario->where('estado', 'vencida')->count())
            @if ($nVencidas)
                <span class="badge-red">{{ $nVencidas }} vencida(s)</span>
                @can('notificaciones.enviar')<form method="POST" action="{{ route('vacunacion.recordatorio', $paciente) }}">@csrf<button class="btn-secondary btn-sm">Enviar recordatorio al tutor</button></form>@endcan
            @endif
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="table">
            <thead><tr><th>Vacuna</th><th>Dosis</th><th>Fecha programada</th><th>Estado</th><th>Aplicación</th><th></th></tr></thead>
            <tbody>
            @php($clases = ['aplicada' => 'badge-green', 'vencida' => 'badge-red', 'pendiente' => 'badge-yellow', 'proxima' => 'badge-blue', 'futura' => 'badge-gray'])
            @foreach ($calendario as $c)
                <tr>
                    <td class="font-medium">{{ $c['dosis']->vaccine->nombre }}</td>
                    <td class="text-xs">{{ $c['dosis']->nombre_dosis }}</td>
                    <td>{{ $c['fecha_programada']->format('d/m/Y') }}</td>
                    <td><span class="{{ $clases[$c['estado']] }}">{{ ucfirst($c['estado'] === 'proxima' ? 'próxima' : $c['estado']) }}</span></td>
                    <td class="text-xs">@if ($a = $c['aplicacion']){{ $a->fecha_aplicacion->format('d/m/Y') }} · Lote {{ $a->lot->numero_lote }}@endif</td>
                    <td class="text-right">
                        @if ($a = $c['aplicacion'])
                            @can('esavi.registrar')<a href="{{ route('esavi.create', $a) }}" class="btn-secondary btn-sm">Registrar ESAVI</a>@endcan
                        @elseif (in_array($c['estado'], ['pendiente', 'vencida']))
                            @can('vacunas.aplicar')<a href="{{ route('vacunacion.create', [$paciente, $c['dosis']]) }}" class="btn-primary btn-sm">Aplicar</a>@endcan
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
