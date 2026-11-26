<?php

namespace App\Services\Clinica;

use App\Exceptions\ReglaNegocioException;
use App\Models\Consultation;
use App\Models\LabOrder;
use App\Models\LabOrderItem;
use App\Models\LabTest;
use App\Models\User;
use App\Services\Facturacion\CuentaService;
use App\Services\Portal\NotificacionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Órdenes de laboratorio e imágenes vinculadas a la consulta y registro de resultados.
 */
class LaboratorioService
{
    public function __construct(
        private readonly CuentaService $cuentas,
        private readonly NotificacionService $notificaciones,
    ) {}

    public function solicitar(Consultation $consulta, User $medico, array $examenes, string $prioridad, ?string $indicaciones): LabOrder
    {
        $pruebas = LabTest::whereIn('id', $examenes)->get();
        if ($pruebas->isEmpty()) {
            throw new ReglaNegocioException('Seleccione al menos un examen.');
        }
        $tipos = $pruebas->pluck('tipo')->unique();

        return DB::transaction(function () use ($consulta, $medico, $pruebas, $prioridad, $indicaciones, $tipos) {
            $orden = LabOrder::create([
                'consultation_id' => $consulta->id,
                'patient_id' => $consulta->patient_id,
                'doctor_id' => $medico->id,
                'tipo' => $tipos->count() === 1 ? $tipos->first() : 'laboratorio',
                'prioridad' => $prioridad,
                'estado' => 'solicitada',
                'indicaciones' => $indicaciones,
            ]);

            foreach ($pruebas as $prueba) {
                $orden->items()->create(['lab_test_id' => $prueba->id, 'ref_min' => $prueba->ref_min, 'ref_max' => $prueba->ref_max]);
                if ($prueba->precio > 0) {
                    $this->cuentas->agregarCargo($consulta->patient, 'laboratorio', "{$prueba->nombre} ({$orden->codigo})", 1, $prueba->precio, $orden);
                }
            }

            return $orden->load('items.labTest');
        });
    }

    public static function esAnormal(?float $valor, ?float $min, ?float $max): bool
    {
        if ($valor === null) {
            return false;
        }

        return ($min !== null && $valor < $min) || ($max !== null && $valor > $max);
    }

    /**
     * @param  array<int, array{valor?: mixed, resultado_texto?: ?string, archivo?: ?UploadedFile, anormal?: mixed}>  $resultados
     */
    public function registrarResultados(LabOrder $orden, array $resultados, User $usuario): LabOrder
    {
        DB::transaction(function () use ($orden, $resultados, $usuario) {
            foreach ($orden->items as $item) {
                $r = $resultados[$item->id] ?? null;
                if (! $r) {
                    continue;
                }
                $valor = isset($r['valor']) && $r['valor'] !== '' ? (float) $r['valor'] : null;
                $texto = $r['resultado_texto'] ?? null;
                $archivo = $r['archivo'] ?? null;
                if ($valor === null && ! $texto && ! $archivo) {
                    continue;
                }

                $datos = [
                    'valor' => $valor,
                    'resultado_texto' => $texto,
                    'es_anormal' => self::esAnormal($valor, $item->ref_min, $item->ref_max) || ! empty($r['anormal']),
                    'resultado_at' => now(),
                    'registrado_por' => $usuario->id,
                ];
                if ($archivo instanceof UploadedFile) {
                    $datos['archivo_path'] = $archivo->store('resultados', 'local');
                    $datos['archivo_nombre'] = $archivo->getClientOriginalName();
                }
                $item->update($datos);
            }
        });

        $orden->refresh()->load('items');
        $completa = $orden->items->every(fn (LabOrderItem $i) => $i->resultado_at !== null);
        if ($completa && $orden->estado !== 'completada') {
            $orden->update(['estado' => 'completada']);
            $this->notificaciones->resultadoListo($orden);
        }

        return $orden;
    }
}
