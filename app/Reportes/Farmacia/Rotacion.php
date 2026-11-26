<?php

namespace App\Reportes\Farmacia;

use App\Models\DispensationItem;
use App\Models\InventoryMovement;
use App\Models\Medication;
use App\Reportes\Reporte;

class Rotacion extends Reporte
{
    public function clave(): string { return 'rotacion'; }

    public function titulo(): string { return 'Rotación y consumo de medicamentos'; }

    public function modulo(): string { return 'Farmacia e Inventario'; }

    public function permiso(): string { return 'reportes.farmacia'; }

    public function descripcion(): string
    {
        return 'Mayor y menor rotación por medicamento y consumo por servicio, para optimizar compras.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'agrupar' => ['select', 'Agrupar por', ['medicamento' => 'Medicamento', 'servicio' => 'Servicio / área']],
        ];
    }

    public function columnas(): array
    {
        return ['Medicamento / Servicio', 'Unidades consumidas', 'Stock actual', 'Índice de rotación', 'Días de cobertura', 'Clasificación'];
    }

    public function filas(array $f): array
    {
        return ($f['agrupar'] ?? '') === 'servicio' ? $this->porServicio($f) : $this->porMedicamento($f);
    }

    private function porMedicamento(array $f): array
    {
        $dias = max(1, $this->desde($f)->diffInDays($this->hasta($f)));

        $consumo = InventoryMovement::query()
            ->whereIn('tipo', ['dispensacion', 'vacunacion'])
            ->whereBetween('created_at', [$this->desde($f), $this->hasta($f)])
            ->selectRaw('medication_id, SUM(-cantidad) as consumido')
            ->groupBy('medication_id')->pluck('consumido', 'medication_id');

        $filas = Medication::where('activo', true)->get()->map(function (Medication $m) use ($consumo, $dias) {
            $consumido = (int) ($consumo[$m->id] ?? 0);
            $stock = $m->stockTotal();
            $indice = $stock > 0 ? round($consumido / $stock, 2) : ($consumido > 0 ? 99 : 0);
            $cobertura = $consumido > 0 ? (int) round($stock / ($consumido / $dias)) : null;

            return [
                $m->nombre.' '.$m->concentracion,
                $consumido,
                $stock,
                $indice,
                $cobertura === null ? 'Sin consumo' : $cobertura,
                match (true) {
                    $consumido === 0 => 'Sin rotación',
                    $cobertura !== null && $cobertura < 15 => 'Alta rotación - riesgo de desabastecimiento',
                    $indice >= 1 => 'Alta rotación',
                    $indice >= 0.3 => 'Rotación media',
                    default => 'Baja rotación',
                },
            ];
        })->sortByDesc(fn ($r) => $r[1])->values()->all();

        return $filas;
    }

    private function porServicio(array $f): array
    {
        $dispensado = DispensationItem::query()
            ->join('dispensations', 'dispensations.id', '=', 'dispensation_items.dispensation_id')
            ->join('prescriptions', 'prescriptions.id', '=', 'dispensations.prescription_id')
            ->leftJoin('consultations', 'consultations.id', '=', 'prescriptions.consultation_id')
            ->whereBetween('dispensations.fecha', [$this->desde($f), $this->hasta($f)])
            ->selectRaw("COALESCE(consultations.servicio, 'sin_servicio') as servicio, SUM(dispensation_items.cantidad) as total")
            ->groupBy('servicio')->pluck('total', 'servicio');

        $vacunas = (int) InventoryMovement::where('tipo', 'vacunacion')
            ->whereBetween('created_at', [$this->desde($f), $this->hasta($f)])->sum('cantidad');

        $nombres = \App\Models\Appointment::SERVICIOS + ['sin_servicio' => 'Receta sin consulta asociada'];
        $filas = $dispensado->map(fn ($total, $servicio) => [$nombres[$servicio] ?? $servicio, (int) $total, '—', '—', '—', 'Farmacia'])->values()->all();
        $filas[] = ['Inmunizaciones (vacunatorio)', abs($vacunas), '—', '—', '—', 'Vacunatorio'];

        return collect($filas)->sortByDesc(fn ($r) => $r[1])->values()->all();
    }

    public function grafico(array $f, array $filas): ?array
    {
        $top = array_slice($filas, 0, 10);

        return [
            'tipo' => 'bar',
            'labels' => array_column($top, 0),
            'series' => ['Unidades consumidas' => array_column($top, 1)],
        ];
    }

    public function resumen(array $f, array $filas): array
    {
        return [
            'Ítems' => count($filas),
            'Unidades consumidas' => collect($filas)->sum(fn ($r) => $r[1]),
            'Con riesgo de desabastecimiento' => collect($filas)->filter(fn ($r) => str_contains((string) $r[5], 'riesgo'))->count(),
            'Sin rotación' => collect($filas)->filter(fn ($r) => $r[5] === 'Sin rotación')->count(),
        ];
    }
}
