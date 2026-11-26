<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><title>{{ $orden->codigo }}</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:10px}h1{font-size:15px;color:#133a63;margin:0}table{width:100%;border-collapse:collapse;margin-top:8px}th,td{border:1px solid #cbd5e1;padding:4px}th{background:#d9edff;text-align:left}.a{color:#b91c1c;font-weight:bold}</style></head><body>
<h1>Clínica Pediátrica con Farmacia Integrada</h1>
<p><strong>RESULTADOS {{ $orden->codigo }}</strong> · Paciente: {{ $orden->patient->nombre_completo }} ({{ $orden->patient->historia_clinica }}) · Solicitado por {{ $orden->doctor->name }} el {{ $orden->created_at->format('d/m/Y') }}</p>
<table><thead><tr><th>Examen</th><th>Resultado</th><th>Referencia</th></tr></thead><tbody>
@foreach ($orden->items as $it)<tr><td>{{ $it->labTest->nombre }}</td><td class="{{ $it->es_anormal ? 'a' : '' }}">{{ $it->valor !== null ? (float) $it->valor.' '.$it->labTest->unidad : '' }} {{ $it->resultado_texto }} {{ $it->es_anormal ? '(ANORMAL)' : '' }}</td><td>@if ($it->ref_min !== null){{ (float) $it->ref_min }} – {{ (float) $it->ref_max }} {{ $it->labTest->unidad }}@endif</td></tr>@endforeach
</tbody></table></body></html>
