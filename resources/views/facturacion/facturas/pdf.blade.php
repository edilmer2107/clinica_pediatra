<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><title>{{ $factura->numero }}</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:10px}h1{font-size:15px;color:#133a63;margin:0}table{width:100%;border-collapse:collapse;margin-top:8px}th,td{border:1px solid #cbd5e1;padding:4px}th{background:#d9edff;text-align:left}.r{text-align:right}</style></head><body>
<h1>Clínica Pediátrica con Farmacia Integrada</h1>
<p><strong>BOLETA DE VENTA ELECTRÓNICA {{ $factura->numero }}</strong> @if ($factura->estado === 'anulada')<strong style="color:#b91c1c">— ANULADA</strong>@endif<br>
Fecha: {{ $factura->fecha_emision->format('d/m/Y') }} · Cliente: {{ $factura->tutor?->nombre_completo }} (DNI {{ $factura->tutor?->dni }}) · Paciente: {{ $factura->patient->nombre_completo }}</p>
<table><thead><tr><th>Descripción</th><th>Cant.</th><th>P. unit.</th><th>Importe</th></tr></thead><tbody>
@foreach ($factura->charges as $c)<tr><td>{{ $c->descripcion }}</td><td>{{ (float) $c->cantidad }}</td><td class="r">{{ number_format($c->precio_unitario, 2) }}</td><td class="r">{{ number_format($c->subtotal, 2) }}</td></tr>@endforeach
</tbody></table>
<table style="width:40%;margin-left:60%"><tr><td>Op. gravada</td><td class="r">S/ {{ number_format($factura->subtotal, 2) }}</td></tr><tr><td>IGV 18 %</td><td class="r">S/ {{ number_format($factura->igv, 2) }}</td></tr><tr><td><strong>TOTAL</strong></td><td class="r"><strong>S/ {{ number_format($factura->total, 2) }}</strong></td></tr></table>
<p style="font-size:8px;color:#64748b">Representación impresa de comprobante emitido con fines académicos.</p>
</body></html>
