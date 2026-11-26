<?php

namespace App\Reportes\Pacientes;

use App\Models\Patient;
use App\Reportes\Reporte;
use App\Services\Pacientes\VacunacionService;

class VacunasPendientes extends Reporte
{
    public function __construct(private readonly VacunacionService $vacunas) {}

    public function clave(): string { return 'vacunas-pendientes'; }

    public function titulo(): string { return 'Pacientes con vacunas pendientes o vencidas'; }

    public function modulo(): string { return 'Pacientes Pediátricos'; }

    public function permiso(): string { return 'reportes.clinicos'; }

    public function descripcion(): string
    {
        return 'Listado operativo para programar campañas de recuperación o enviar recordatorios.';
    }

    public function filtros(): array
    {
        return [
            'estado' => ['select', 'Estado', ['vencida' => 'Solo vencidas', 'pendiente' => 'Solo pendientes']],
            'tipo_paciente' => ['select', 'Tipo de paciente', self::gruposEdad()],
        ];
    }

    public function columnas(): array
    {
        return ['HC', 'Paciente', 'Edad', 'Vacuna', 'Dosis', 'Fecha programada', 'Estado', 'Tutor', 'Celular'];
    }

    public function filas(array $f): array
    {
        $filas = [];
        $pacientes = Patient::with(['tutors', 'immunizations'])
            ->when($f['tipo_paciente'] ?? null, function ($q, $g) {
                [$min, $max] = self::rangoNacimiento($g);
                $q->whereBetween('fecha_nacimiento', [$min, $max]);
            })
            ->where('activo', true)->orderBy('apellidos')->get();

        foreach ($pacientes as $p) {
            $tutor = $p->tutorPrincipal();
            foreach ($this->vacunas->calendario($p) as $c) {
                if (! in_array($c['estado'], ['vencida', 'pendiente'], true)) {
                    continue;
                }
                if (! empty($f['estado']) && $c['estado'] !== $f['estado']) {
                    continue;
                }
                $filas[] = [
                    $p->historia_clinica, $p->nombre_completo, $p->edad_texto,
                    $c['dosis']->vaccine->nombre, $c['dosis']->nombre_dosis,
                    $c['fecha_programada']->format('d/m/Y'), ucfirst($c['estado']),
                    $tutor?->nombre_completo, $tutor?->telefono,
                ];
            }
        }

        return $filas;
    }

    public function resumen(array $f, array $filas): array
    {
        $c = collect($filas);

        return [
            'Dosis atrasadas' => $c->count(),
            'Pacientes' => $c->pluck(0)->unique()->count(),
            'Vencidas' => $c->where(6, 'Vencida')->count(),
            'Pendientes' => $c->where(6, 'Pendiente')->count(),
        ];
    }
}
