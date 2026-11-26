<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reporte->titulo() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        h1 { font-size: 14px; margin: 0; color: #133a63; }
        h2 { font-size: 11px; margin: 2px 0 8px; }
        .meta { color: #64748b; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #d9edff; text-align: left; padding: 4px; border: 1px solid #cbd5e1; }
        td { padding: 3px 4px; border: 1px solid #e2e8f0; }
        .resumen td { border: none; padding: 2px 8px 2px 0; }
    </style>
</head>
<body>
    <h1>Clínica Pediátrica con Farmacia Integrada</h1>
    <h2>{{ $reporte->titulo() }}</h2>
    <div class="meta">Período: {{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }} · Generado: {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}</div>
    <table class="resumen" style="width:auto; margin-bottom:8px;">
        @foreach ($resumen as $etiqueta => $valor)<tr><td><strong>{{ $etiqueta }}:</strong></td><td>{{ $valor }}</td></tr>@endforeach
    </table>
    <table>
        <thead><tr>@foreach ($reporte->columnas() as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($filas as $fila)
            <tr>@foreach ($fila as $valor)<td>{{ $valor }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($reporte->columnas()) }}">Sin datos.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
