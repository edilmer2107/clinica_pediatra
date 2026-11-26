<?php

namespace App\Reportes\Pacientes;

use App\Models\AnthropometricMeasurement;
use App\Reportes\Reporte;

class CrecimientoAlertas extends Reporte
{
    public function clave(): string { return 'crecimiento-alertas'; }

    public function titulo(): string { return 'Crecimiento y desarrollo con alertas'; }

    public function modulo(): string { return 'Pacientes Pediátricos'; }

    public function permiso(): string { return 'reportes.clinicos'; }

    public function descripcion(): string
    {
        return 'Pacientes con valores fuera de rango o caída/cruce significativo de percentiles, para priorizar seguimiento.';
    }

    public function filtros(): array
    {
        return parent::filtros() + [
            'tipo_alerta' => ['select', 'Tipo de alerta', ['rango' => 'Fuera de rango (Z)', 'cruce' => 'Cruce de percentiles']],
            'tipo_paciente' => ['select', 'Tipo de paciente', self::gruposEdad()],
        ];
    }

    public function columnas(): array
    {
        return ['Fecha', 'HC', 'Paciente', 'Edad', 'Peso (kg)', 'Talla (cm)', 'Z P/E', 'Z T/E', 'Z IMC/E', 'Alertas', 'Tutor / celular'];
    }

    public function filas(array $f): array
    {
        return AnthropometricMeasurement::with('patient.tutors')
            ->where('tiene_alerta', true)
            ->whereBetween('fecha', [$this->desde($f), $this->hasta($f)])
            ->when($f['tipo_paciente'] ?? null, function ($q, $g) {
                [$min, $max] = self::rangoNacimiento($g);
                $q->whereHas('patient', fn ($p) => $p->whereBetween('fecha_nacimiento', [$min, $max]));
            })
            ->orderByDesc('fecha')->get()
            ->filter(fn ($m) => empty($f['tipo_alerta']) || collect($m->alertas)->contains('tipo', $f['tipo_alerta']))
            ->unique('patient_id')
            ->map(fn ($m) => [
                $m->fecha->format('d/m/Y'),
                $m->patient->historia_clinica,
                $m->patient->nombre_completo,
                intdiv($m->edad_dias, 30).' m',
                $m->peso_kg,
                $m->talla_cm,
                $m->z_peso_edad ?? '—',
                $m->z_talla_edad ?? '—',
                $m->z_imc_edad ?? '—',
                collect($m->alertas)->where('tipo', '!=', 'info')->pluck('mensaje')->implode(' | '),
                ($m->patient->tutorPrincipal()?->nombre_completo ?? '').' '.($m->patient->tutorPrincipal()?->telefono ?? ''),
            ])->values()->all();
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);

        return [
            'Pacientes con alerta' => $c->count(),
            'Con cruce de percentiles' => $c->filter(fn ($r) => str_contains($r[9], 'líneas de percentil'))->count(),
            'Con bajo peso / talla baja' => $c->filter(fn ($r) => str_contains($r[9], 'Bajo peso') || str_contains($r[9], 'Talla baja'))->count(),
            'Con sobrepeso / obesidad' => $c->filter(fn ($r) => str_contains($r[9], 'Sobrepeso') || str_contains($r[9], 'Obesidad'))->count(),
        ];
    }
}
