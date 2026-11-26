<?php

namespace App\Reportes\Portal;

use App\Models\DocumentAccess;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Reportes\Reporte;

class EntregaDocumentos extends Reporte
{
    public function clave(): string { return 'entrega-documentos'; }

    public function titulo(): string { return 'Entrega de resultados y recetas'; }

    public function modulo(): string { return 'Comunicación y Portal de Tutores'; }

    public function permiso(): string { return 'reportes.comunicacion'; }

    public function descripcion(): string
    {
        return 'Tutores que visualizaron o descargaron recetas y resultados, y quiénes aún no lo hicieron.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'documento' => ['select', 'Documento', ['receta' => 'Recetas', 'resultado' => 'Resultados']],
            'estado' => ['select', 'Estado', ['entregado' => 'Visto/descargado', 'pendiente' => 'Pendiente']],
        ];
    }

    public function columnas(): array
    {
        return ['Documento', 'Código', 'Fecha', 'Paciente', 'Tutor(es)', 'Estado', 'Primer acceso', 'Visualizaciones', 'Descargas'];
    }

    public function filas(array $f): array
    {
        $accesos = DocumentAccess::get()->groupBy(fn ($a) => $a->documento_tipo.'|'.$a->documento_id);
        $filas = [];

        if (($f['documento'] ?? '') !== 'resultado') {
            foreach (Prescription::with('patient.tutors')->where('estado', '!=', 'anulada')->whereBetween('created_at', [$this->desde($f), $this->hasta($f)])->get() as $r) {
                $filas[] = $this->fila('Receta', $r->codigo, $r->created_at, $r->patient, $accesos->get('receta|'.$r->id));
            }
        }
        if (($f['documento'] ?? '') !== 'receta') {
            foreach (LabOrder::with('patient.tutors')->where('estado', 'completada')->whereBetween('created_at', [$this->desde($f), $this->hasta($f)])->get() as $o) {
                $filas[] = $this->fila('Resultado', $o->codigo, $o->created_at, $o->patient, $accesos->get('resultado|'.$o->id));
            }
        }

        return collect($filas)
            ->filter(fn ($r) => empty($f['estado']) || ($f['estado'] === 'entregado') === ($r[5] === 'Entregado'))
            ->sortBy(fn ($r) => $r[2])->values()->all();
    }

    private function fila(string $tipo, string $codigo, $fecha, $paciente, $accesos): array
    {
        $accesos ??= collect();

        return [
            $tipo, $codigo, $fecha->format('d/m/Y'), $paciente->nombre_completo,
            $paciente->tutors->map(fn ($t) => $t->nombre_completo.' ('.$t->telefono.')')->implode('; '),
            $accesos->isEmpty() ? 'Pendiente' : 'Entregado',
            $accesos->min('created_at')?->format('d/m/Y H:i') ?? '—',
            $accesos->where('accion', 'visualizacion')->count(),
            $accesos->where('accion', 'descarga')->count(),
        ];
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);
        $total = max(1, $c->count());

        return ['Documentos' => $c->count(), 'Entregados' => $c->where(5, 'Entregado')->count(), 'Pendientes' => $c->where(5, 'Pendiente')->count(),
            '% entregados' => round($c->where(5, 'Entregado')->count() / $total * 100, 1).' %'];
    }
}
