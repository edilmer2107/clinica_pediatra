<?php

namespace App\Reportes\Farmacia;

use App\Http\Controllers\Farmacia\ErrorMedicacionController;
use App\Models\MedicationError;
use App\Reportes\Reporte;

class ErroresMedicacion extends Reporte
{
    public function clave(): string { return 'errores-medicacion'; }

    public function titulo(): string { return 'Errores de dispensación y prescripción'; }

    public function modulo(): string { return 'Farmacia e Inventario'; }

    public function permiso(): string { return 'reportes.farmacia'; }

    public function descripcion(): string
    {
        return 'Errores detectados con evidencia documental y medidas correctivas, para auditorías.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'tipo' => ['select', 'Tipo', ['prescripcion' => 'Prescripción', 'dispensacion' => 'Dispensación']],
            'gravedad' => ['select', 'Gravedad', ['sin_dano' => 'Sin daño', 'leve' => 'Leve', 'moderado' => 'Moderado', 'grave' => 'Grave']],
        ];
    }

    public function columnas(): array
    {
        return ['Fecha', 'Tipo', 'Categoría', 'Gravedad', 'Paciente', 'Receta / Dispensación', 'Descripción', 'Medida correctiva', 'Evidencia', 'Reportado por'];
    }

    public function filas(array $f): array
    {
        return MedicationError::with(['patient', 'prescription', 'dispensation', 'reportadoPor'])
            ->whereBetween('fecha', [$this->desde($f), $this->hasta($f)])
            ->when($f['tipo'] ?? null, fn ($q, $v) => $q->where('tipo', $v))
            ->when($f['gravedad'] ?? null, fn ($q, $v) => $q->where('gravedad', $v))
            ->orderByDesc('fecha')->get()
            ->map(fn (MedicationError $e) => [
                $e->fecha->format('d/m/Y'),
                ucfirst($e->tipo),
                ErrorMedicacionController::CATEGORIAS[$e->categoria] ?? $e->categoria,
                str_replace('_', ' ', $e->gravedad),
                $e->patient?->nombre_completo ?? '—',
                $e->prescription?->codigo ?? $e->dispensation?->codigo ?? '—',
                $e->descripcion,
                $e->medida_correctiva,
                $e->evidencia_nombre ?? 'Sin evidencia',
                $e->reportadoPor?->name,
            ])->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);

        return [
            'Total de errores' => $c->count(),
            'De prescripción' => $c->where(1, 'Prescripcion')->count(),
            'De dispensación' => $c->where(1, 'Dispensacion')->count(),
            'Graves o moderados' => $c->whereIn(3, ['grave', 'moderado'])->count(),
        ];
    }
}
