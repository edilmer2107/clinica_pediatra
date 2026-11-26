<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Reportes\CatalogoReportes;
use App\Reportes\Reporte;
use App\Services\Reportes\ExportadorReportes;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    public function index(Request $request): View
    {
        $reportes = CatalogoReportes::todos()
            ->filter(fn (Reporte $r) => $request->user()->can($r->permiso()))
            ->groupBy(fn (Reporte $r) => $r->modulo());

        return view('reportes.index', compact('reportes'));
    }

    public function show(Request $request, string $reporte, ExportadorReportes $exportador): View|Response
    {
        $r = $this->resolver($request, $reporte);
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'formato' => ['nullable', 'in:pdf,excel'],
        ]);

        $filtros = $r->valoresFiltros($request);
        $filas = $r->filas($filtros);
        $resumen = $r->resumen($filtros, $filas);

        return match ($request->query('formato')) {
            'pdf' => $exportador->pdf($r, $filtros, $filas, $resumen),
            'excel' => $exportador->excel($r, $filtros, $filas, $resumen),
            default => view('reportes.show', [
                'reporte' => $r,
                'filtros' => $filtros,
                'filas' => $filas,
                'resumen' => $resumen,
                'grafico' => $r->grafico($filtros, $filas),
            ]),
        };
    }

    private function resolver(Request $request, string $clave): Reporte
    {
        $reporte = CatalogoReportes::buscar($clave);
        abort_if(! $reporte, 404);
        abort_unless($request->user()->can($reporte->permiso()), 403);

        return $reporte;
    }
}
