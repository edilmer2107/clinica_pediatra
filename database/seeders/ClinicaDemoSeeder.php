<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Cie10Code;
use App\Models\Consultation;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\User;
use App\Services\Clinica\CitaService;
use App\Services\Clinica\ConsultaService;
use App\Services\Clinica\LaboratorioService;
use App\Services\Clinica\PlantillaHistoriaService;
use App\Services\Clinica\RecetaService;
use App\Services\Farmacia\DispensacionService;
use App\Services\Farmacia\InventarioService;
use App\Models\LabTest;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Actividad clínica ficticia: citas, consultas, recetas, dispensaciones y laboratorio.
 */
class ClinicaDemoSeeder extends Seeder
{
    public function run(CitaService $citas, ConsultaService $consultas, RecetaService $recetas, DispensacionService $dispensacion,
        InventarioService $inventario, LaboratorioService $laboratorio): void
    {
        if (Consultation::exists()) {
            return;
        }
        mt_srand(77);

        $medicos = User::role('medico')->get();
        $farmaceutico = User::where('email', 'farmacia@clinica.test')->first();
        $recepcion = User::where('email', 'recepcion@clinica.test')->first();
        $pacientes = Patient::with('tutors')->get();

        // receta típica por diagnóstico: CIE-10 => [código medicamento, mg/kg/día, frecuencia, días]
        $tratamientos = [
            'J02.9' => [['MED-001', 50, 8, 10], ['MED-002', 60, 6, 3]],
            'H66.9' => [['MED-001', 80, 12, 10], ['MED-004', 30, 8, 3]],
            'J00' => [['MED-002', 60, 6, 3], ['MED-010', 0.25, 24, 5]],
            'A09' => [['MED-014', null, 8, 3]],
            'B82.9' => [['MED-012', 30, 8, 7]],
            'D50.9' => [['MED-011', 3, 24, 30]],
            'J21.9' => [['MED-009', 0.3, 8, 5]],
            'L20.9' => [['MED-010', 0.25, 24, 7]],
            'J18.9' => [['MED-001', 90, 12, 7], ['MED-002', 60, 6, 3]],
            'N39.0' => [['MED-006', 50, 6, 7]],
            'R56.0' => [['MED-016', 5, 12, 30], ['MED-002', 60, 6, 3]],
            'Z00.1' => [],
        ];
        $dxCodigos = array_keys($tratamientos);
        $n = 0;

        foreach ($pacientes as $paciente) {
            $visitas = $paciente->edadEnDias() < 30 ? 1 : mt_rand(2, 4);
            for ($v = 0; $v < $visitas; $v++) {
                $n++;
                $medico = $medicos[$n % $medicos->count()];
                $fecha = today()->subDays(mt_rand(2, 85))->setTime(mt_rand(8, 17), [0, 30][mt_rand(0, 1)]);
                if ($fecha->isSunday()) {
                    $fecha->subDay();
                }
                $tele = $n % 6 === 0;
                $estadoAzar = mt_rand(1, 100);
                $estado = $estadoAzar <= 72 ? 'atendida' : ($estadoAzar <= 86 ? 'cancelada' : 'no_asistio');

                $cita = Appointment::create([
                    'patient_id' => $paciente->id, 'doctor_id' => $medico->id, 'tutor_id' => $paciente->tutors->first()?->id,
                    'fecha_hora' => $fecha, 'duracion_minutos' => 30, 'modalidad' => $tele ? 'telemedicina' : 'presencial',
                    'servicio' => $tele ? 'telemedicina' : ($v === 0 ? 'control_nino_sano' : 'general'),
                    'estado' => 'programada', 'motivo' => $tele ? 'Teleconsulta de seguimiento' : 'Control / malestar',
                    'enlace_telemedicina' => $tele ? config('clinica.telemedicina_url').'/ClinicaPediatrica-demo'.$n : null,
                    'creado_por' => $recepcion->id, 'created_at' => $fecha->copy()->subDays(5),
                ]);

                if ($estado !== 'atendida') {
                    $cita->update(['estado' => $estado, 'motivo_cancelacion' => $estado === 'cancelada' ? 'Tutor no puede asistir' : null,
                        'cancelada_at' => $estado === 'cancelada' ? $fecha->copy()->subDays(2) : null]);

                    continue;
                }

                $dx = $cita->servicio === 'control_nino_sano' ? 'Z00.1' : $dxCodigos[mt_rand(0, count($dxCodigos) - 2)];
                $consulta = $this->consulta($consultas, $paciente, $medico, $cita, $dx);
                $consulta->update(['fecha' => $fecha, 'created_at' => $fecha, 'estado' => 'cerrada']);

                // Receta
                $items = [];
                foreach ($tratamientos[$dx] as [$codigo, $dosis, $frec, $dias]) {
                    $med = Medication::where('codigo', $codigo)->first();
                    $items[] = ['medication_id' => $med->id, 'metodo_calculo' => 'peso', 'dosis_indicada' => $dosis ?? 50,
                        'frecuencia_horas' => $frec, 'duracion_dias' => $dias, 'via' => 'oral', 'indicaciones' => 'Según indicación médica',
                        'justificacion' => $dosis === null ? 'Solución de rehidratación según pérdidas' : null];
                }
                $peso = $paciente->measurements()->reorder('fecha', 'desc')->value('peso_kg') ?? 12;
                $talla = $paciente->measurements()->reorder('fecha', 'desc')->value('talla_cm') ?? 85;
                if ($items) {
                    auth()->setUser($medico);
                    try {
                        $receta = $recetas->emitir($paciente, $medico, ['peso_kg' => $peso, 'talla_cm' => $talla, 'items' => $items], $consulta);
                    } catch (\App\Exceptions\ReglaNegocioException) {
                        // Dosis fuera de rango: el médico la justifica (queda registrada la alerta)
                        $items = array_map(fn ($it) => $it + ['justificacion' => 'Dosis ajustada por criterio clínico'], array_map(fn ($it) => array_filter($it, fn ($v, $k) => $k !== 'justificacion' || $v, ARRAY_FILTER_USE_BOTH), $items));
                        $receta = $recetas->emitir($paciente, $medico, ['peso_kg' => $peso, 'talla_cm' => $talla, 'items' => $items], $consulta);
                    }
                    $receta->update(['created_at' => $fecha]);

                    // Dispensación (la mayoría completa, algunas pendientes)
                    if (mt_rand(1, 100) <= 80 && $fecha->lt(today()->subDay())) {
                        auth()->setUser($farmaceutico);
                        $lineas = [];
                        foreach ($receta->items as $it) {
                            $lote = $inventario->sugerirLote($it->medication);
                            if ($lote && $lote->cantidad_actual >= $it->cantidad) {
                                $lineas[] = ['prescription_item_id' => $it->id, 'medication_id' => $it->medication_id, 'lot_id' => $lote->id, 'cantidad' => $it->cantidad];
                            }
                        }
                        if ($lineas) {
                            $tutor = $paciente->tutors->first();
                            $d = $dispensacion->dispensar($receta, $lineas, $farmaceutico, $tutor?->id);
                            $d->update(['fecha' => $fecha->copy()->addHour()]);
                        }
                    }
                }

                // Laboratorio en algunos casos
                if (in_array($dx, ['D50.9', 'N39.0', 'J18.9', 'B82.9'], true)) {
                    auth()->setUser($medico);
                    $examenes = match ($dx) {
                        'D50.9' => LabTest::whereIn('codigo', ['LAB-HB', 'LAB-FER'])->pluck('id')->all(),
                        'N39.0' => LabTest::whereIn('codigo', ['LAB-ORI', 'LAB-LEU'])->pluck('id')->all(),
                        'J18.9' => LabTest::whereIn('codigo', ['LAB-LEU', 'LAB-PCR', 'IMG-RXT'])->pluck('id')->all(),
                        default => LabTest::whereIn('codigo', ['LAB-PAR'])->pluck('id')->all(),
                    };
                    $orden = $laboratorio->solicitar($consulta, $medico, $examenes, 'rutina', null);
                    $orden->update(['created_at' => $fecha]);
                    if ($fecha->lt(today()->subDays(3))) {
                        $res = [];
                        foreach ($orden->items as $it) {
                            $t = $it->labTest;
                            $valor = $t->ref_min !== null ? round(match ($dx) {
                                'D50.9' => $t->codigo === 'LAB-HB' ? 9.4 : 8,
                                'N39.0' => $t->codigo === 'LAB-ORI' ? 25 : 16.8,
                                'J18.9' => $t->codigo === 'LAB-PCR' ? 38 : 18.2,
                                default => ($t->ref_min + $t->ref_max) / 2,
                            }, 2) : null;
                            $res[$it->id] = ['valor' => $valor, 'resultado_texto' => $t->tipo === 'imagen' ? 'Infiltrado en base derecha compatible con neumonía.' : ($t->codigo === 'LAB-PAR' ? 'Quistes de Giardia lamblia.' : null),
                                'anormal' => $t->ref_min === null];
                        }
                        $laboratorio->registrarResultados($orden, $res, $medico);
                    }
                }
            }
        }

        // Citas futuras (próximos 7 días hábiles)
        auth()->setUser($recepcion);
        $dia = today()->addDay();
        foreach ($pacientes->take(12) as $i => $paciente) {
            while ($dia->isSunday()) {
                $dia->addDay();
            }
            $hora = 8 + ($i % 9);
            try {
                $citas->agendar([
                    'patient_id' => $paciente->id, 'doctor_id' => $medicos[$i % $medicos->count()]->id,
                    'fecha_hora' => $dia->copy()->setTime($hora, $i % 2 ? 30 : 0), 'duracion_minutos' => 30,
                    'modalidad' => $i % 4 === 0 ? 'telemedicina' : 'presencial', 'servicio' => $i % 3 === 0 ? 'control_nino_sano' : 'general',
                    'motivo' => $i % 3 === 0 ? 'Control de crecimiento y desarrollo' : 'Seguimiento',
                ]);
            } catch (\Throwable) {
                // horario ocupado: se omite
            }
            if ($i % 2 === 1) {
                $dia->addDay();
            }
        }
        // Citas de hoy (para la agenda del día)
        foreach ($pacientes->slice(12, 4)->values() as $i => $paciente) {
            $hora = now()->addHours($i + 1)->minute(0);
            if ($hora->hour >= 8 && $hora->hour < 19 && ! $hora->isSunday()) {
                try {
                    $citas->agendar(['patient_id' => $paciente->id, 'doctor_id' => $medicos[$i % 2]->id, 'fecha_hora' => $hora,
                        'duracion_minutos' => 30, 'modalidad' => 'presencial', 'servicio' => 'general', 'motivo' => 'Consulta del día']);
                } catch (\Throwable) {
                }
            }
        }

        auth()->logout();
    }

    private function consulta(ConsultaService $servicio, Patient $paciente, User $medico, Appointment $cita, string $dx): Consultation
    {
        $plantilla = PlantillaHistoriaService::determinar($paciente);
        $interrogatorio = [];
        foreach (PlantillaHistoriaService::interrogatorio($plantilla) as $campo => [$etiqueta]) {
            $interrogatorio[$campo] = 'Sin alteraciones referidas';
        }
        $examen = array_fill_keys(array_keys(PlantillaHistoriaService::examenFisico($plantilla)), 'Sin alteraciones');
        $cie = Cie10Code::where('codigo', $dx)->first();

        return $servicio->registrar($paciente, $medico, [
            'servicio' => $cita->servicio, 'motivo_consulta' => $dx === 'Z00.1' ? 'Control de crecimiento y desarrollo' : $cie->descripcion,
            'interrogatorio' => $interrogatorio, 'examen_fisico' => $examen, 'temperatura' => $dx === 'Z00.1' ? 36.6 : 38.1,
            'frecuencia_cardiaca' => 110, 'frecuencia_respiratoria' => 28, 'saturacion_o2' => 97,
            'diagnosticos' => [$cie->id], 'tipo_diagnostico' => ['definitivo'], 'plan_tratamiento' => 'Tratamiento sintomático y controles.',
            'indicaciones' => 'Control en 7 días o antes si presenta signos de alarma.',
        ], $cita);
    }
}
