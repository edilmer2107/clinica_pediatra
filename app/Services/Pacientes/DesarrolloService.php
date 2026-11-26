<?php

namespace App\Services\Pacientes;

use App\Models\Milestone;
use App\Models\Patient;
use Illuminate\Support\Collection;

/**
 * Evaluación de hitos del desarrollo psicomotor por dominios.
 */
class DesarrolloService
{
    /** Hitos aplicables a la edad del paciente (esperados hasta su edad + 3 meses). */
    public function hitosAplicables(Patient $paciente): Collection
    {
        $meses = $paciente->edadEnMeses();
        $ultimas = $paciente->milestoneAssessments()->latest('fecha')->get()->unique('milestone_id')->keyBy('milestone_id');

        return Milestone::where('edad_esperada_meses', '<=', $meses + 3)
            ->orderBy('dominio')->orderBy('edad_esperada_meses')->get()
            ->map(fn (Milestone $h) => [
                'hito' => $h,
                'evaluacion' => $ultimas->get($h->id),
                'vencido' => $meses > $h->edad_limite_meses,
            ])
            ->groupBy(fn ($r) => $r['hito']->dominio);
    }

    /** Hay alerta si el hito no se logró y el niño ya superó la edad límite. */
    public static function esAlerta(bool $logrado, int $edadMeses, int $edadLimite): bool
    {
        return ! $logrado && $edadMeses > $edadLimite;
    }
}
