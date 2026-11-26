<?php

namespace App\Services\Pacientes;

use App\Exceptions\ReglaNegocioException;
use App\Models\Immunization;
use App\Models\Lot;
use App\Models\Patient;
use App\Models\User;
use App\Models\VaccineScheduleDose;
use App\Services\AuditService;
use App\Services\Facturacion\CuentaService;
use App\Services\Farmacia\InventarioService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Calendario de vacunación (esquema regular) y registro de aplicación con
 * descuento automático del stock de farmacia.
 */
class VacunacionService
{
    /** Días de anticipación permitidos respecto a la edad mínima de la dosis. */
    public const DIAS_GRACIA_ANTICIPO = 3;

    public function __construct(
        private readonly InventarioService $inventario,
        private readonly CuentaService $cuentas,
        private readonly AuditService $audit,
    ) {}

    /**
     * Estado de cada dosis del esquema para el paciente:
     * aplicada | vencida | pendiente | proxima | futura.
     */
    public function calendario(Patient $paciente, ?Carbon $fecha = null): Collection
    {
        $fecha ??= today();
        $edadDias = (int) $paciente->fecha_nacimiento->diffInDays($fecha);
        $aplicadas = $paciente->immunizations()->with('lot')->get()->keyBy('vaccine_schedule_dose_id');

        return VaccineScheduleDose::with('vaccine')->orderBy('orden')->get()
            ->map(function (VaccineScheduleDose $dosis) use ($edadDias, $aplicadas, $paciente) {
                $aplicacion = $aplicadas->get($dosis->id);

                return [
                    'dosis' => $dosis,
                    'aplicacion' => $aplicacion,
                    'fecha_programada' => $paciente->fecha_nacimiento->copy()->addDays($dosis->edad_dias),
                    'estado' => $aplicacion ? 'aplicada' : self::estado($edadDias, $dosis->edad_dias, $dosis->tolerancia_dias),
                ];
            });
    }

    public static function estado(int $edadDias, int $edadDosis, int $tolerancia): string
    {
        return match (true) {
            $edadDias > $edadDosis + $tolerancia => 'vencida',
            $edadDias >= $edadDosis - self::DIAS_GRACIA_ANTICIPO => 'pendiente',
            $edadDias >= $edadDosis - 30 => 'proxima',
            default => 'futura',
        };
    }

    /** @return array<int, string> nombres de dosis vencidas */
    public function dosisVencidas(Patient $paciente): array
    {
        return $this->calendario($paciente)
            ->where('estado', 'vencida')
            ->map(fn ($d) => $d['dosis']->vaccine->nombre.' - '.$d['dosis']->nombre_dosis)
            ->values()->all();
    }

    public function aplicar(Patient $paciente, VaccineScheduleDose $dosis, Lot $lote, User $usuario, array $extra = []): Immunization
    {
        if (! $paciente->consentimientoVigente()) {
            throw new ReglaNegocioException('El paciente no tiene consentimiento informado vigente del tutor.');
        }

        $vacuna = $dosis->vaccine;
        if (! $vacuna->medication_id || $lote->medication_id !== $vacuna->medication_id) {
            throw new ReglaNegocioException("El lote {$lote->numero_lote} no corresponde a la vacuna {$vacuna->nombre}.");
        }

        if (Immunization::where('patient_id', $paciente->id)->where('vaccine_schedule_dose_id', $dosis->id)->exists()) {
            throw new ReglaNegocioException("La dosis {$dosis->nombre_dosis} de {$vacuna->nombre} ya fue aplicada a este paciente.");
        }

        $fecha = isset($extra['fecha_aplicacion']) ? Carbon::parse($extra['fecha_aplicacion']) : today();
        $edadDias = (int) $paciente->fecha_nacimiento->diffInDays($fecha);
        if ($edadDias < $dosis->edad_dias - self::DIAS_GRACIA_ANTICIPO) {
            throw new ReglaNegocioException("El paciente aún no tiene la edad mínima para {$vacuna->nombre} - {$dosis->nombre_dosis} ({$dosis->edad_dias} días).");
        }

        $anterior = VaccineScheduleDose::where('vaccine_id', $vacuna->id)->where('numero_dosis', '<', $dosis->numero_dosis)
            ->whereNotIn('id', $paciente->immunizations()->pluck('vaccine_schedule_dose_id')->filter())
            ->orderBy('numero_dosis')->first();
        if ($anterior) {
            throw new ReglaNegocioException("Debe aplicarse primero {$vacuna->nombre} - {$anterior->nombre_dosis}.");
        }

        return DB::transaction(function () use ($paciente, $dosis, $lote, $usuario, $extra, $vacuna, $fecha) {
            $inmunizacion = Immunization::create([
                'patient_id' => $paciente->id,
                'vaccine_id' => $vacuna->id,
                'vaccine_schedule_dose_id' => $dosis->id,
                'lot_id' => $lote->id,
                'fecha_aplicacion' => $fecha,
                'sitio_aplicacion' => $extra['sitio_aplicacion'] ?? null,
                'observaciones' => $extra['observaciones'] ?? null,
                'aplicado_por' => $usuario->id,
            ]);

            // Descuento automático de la dosis en el inventario de farmacia
            $this->inventario->descontar($lote, 1, 'vacunacion', $inmunizacion, "Vacunación {$paciente->historia_clinica}");

            if ($vacuna->precio > 0) {
                $this->cuentas->agregarCargo($paciente, 'vacuna', "Aplicación {$vacuna->nombre} - {$dosis->nombre_dosis}", 1, (float) $vacuna->precio, $inmunizacion);
            }

            $this->audit->registrar('aplicar_vacuna', 'pacientes',
                "Aplicación de {$vacuna->nombre} ({$dosis->nombre_dosis}) lote {$lote->numero_lote} a {$paciente->historia_clinica}.", $inmunizacion);

            return $inmunizacion;
        });
    }
}
