<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><title>Receta {{ $receta->codigo }}</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#1e293b}h1{font-size:15px;color:#133a63;margin:0}table{width:100%;border-collapse:collapse;margin-top:10px}th,td{border:1px solid #cbd5e1;padding:4px;text-align:left}th{background:#d9edff}.meta td{border:none;padding:2px}</style></head>
<body>
<h1>Clínica Pediátrica con Farmacia Integrada</h1>
<p>RECETA ELECTRÓNICA N° <strong>{{ $receta->codigo }}</strong> · {{ $receta->created_at->format('d/m/Y H:i') }}</p>
<table class="meta">
<tr><td><strong>Paciente:</strong> {{ $receta->patient->nombre_completo }} ({{ $receta->patient->historia_clinica }})</td><td><strong>Edad:</strong> {{ $receta->patient->edad_texto }}</td></tr>
<tr><td><strong>Peso:</strong> {{ $receta->peso_kg }} kg · <strong>Talla:</strong> {{ $receta->talla_cm ?? '—' }} cm</td><td><strong>Dx:</strong> {{ $receta->consultation?->diagnoses->map(fn ($d) => $d->cie10->codigo.' '.$d->cie10->descripcion)->implode('; ') }}</td></tr>
</table>
<table><thead><tr><th>Medicamento</th><th>Posología</th><th>Duración</th><th>Cantidad</th></tr></thead><tbody>
@foreach ($receta->items as $i)
<tr><td>{{ $i->medication->nombre_completo }}</td><td>{{ (float) $i->dosis_por_toma_mg }} mg @if ($i->volumen_por_toma_ml)({{ $i->volumen_por_toma_ml }} mL)@endif vía {{ $i->via }} cada {{ $i->frecuencia_horas }} h. {{ $i->indicaciones }}</td><td>{{ $i->duracion_dias }} días</td><td>{{ $i->cantidad }} {{ $i->medication->unidad }}</td></tr>
@endforeach
</tbody></table>
<p style="margin-top:40px">______________________________<br>{{ $receta->doctor->name }}<br>{{ $receta->doctor->colegiatura }}</p>
<p style="font-size:8px;color:#64748b">Documento generado electrónicamente. Estado: {{ $receta->estado }}.</p>
</body></html>
