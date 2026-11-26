<?php

namespace Database\Seeders;

use App\Models\AnthropometricMeasurement;
use App\Models\Immunization;
use App\Models\Milestone;
use App\Models\MilestoneAssessment;
use App\Models\Patient;
use App\Models\Tutor;
use App\Models\User;
use App\Models\VaccineScheduleDose;
use App\Services\Farmacia\InventarioService;
use App\Services\Pacientes\PacienteService;
use App\Services\Pacientes\PercentilOmsService;
use App\Services\Pacientes\VacunacionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Pacientes y tutores ficticios para pruebas (no corresponden a personas reales).
 */
class PacientesDemoSeeder extends Seeder
{
    public function run(PacienteService $pacientes, PercentilOmsService $oms, VacunacionService $vacunas, InventarioService $inventario): void
    {
        if (Patient::exists()) {
            return;
        }

        $enfermera = User::where('email', 'enfermeria@clinica.test')->first();
        auth()->setUser(User::where('email', 'recepcion@clinica.test')->first());
        mt_srand(2026);

        // nombres, apellidos, sexo, edad en días, z objetivo (trayectoria), vacunas al día
        $ninos = [
            ['Mateo', 'Quispe Rojas', 'M', 15, [0.2], true],
            ['Valentina', 'Huamán Torres', 'F', 75, [0.0, -0.1], true],
            ['Santiago', 'Flores Medina', 'M', 140, [0.5, 0.4, 0.6], true],
            ['Camila', 'Ramos Castillo', 'F', 200, [0.1, -0.6, -1.4, -2.3], true],   // caída de percentiles
            ['Thiago', 'Vargas Chávez', 'M', 260, [-0.3, -0.2, -0.4], false],        // vacunas vencidas
            ['Luciana', 'Mendoza Salazar', 'F', 330, [1.2, 1.5, 1.9, 2.4], true],    // sobrepeso
            ['Sebastián', 'Cruz Paredes', 'M', 400, [0.0, 0.1, 0.0], true],
            ['Isabella', 'Rojas Gutiérrez', 'F', 470, [-1.0, -1.1, -0.9], false],
            ['Benjamín', 'Torres Díaz', 'M', 560, [-2.4, -2.5, -2.6], true],         // talla/peso bajos
            ['Mía', 'Sánchez León', 'F', 640, [0.3, 0.2], true],
            ['Gael', 'Chávez Ríos', 'M', 760, [0.6, 0.7], false],
            ['Antonella', 'Castro Vega', 'F', 900, [-0.5, -0.4], true],
            ['Joaquín', 'Ruiz Peña', 'M', 1100, [0.0, 0.1], true],
            ['Renata', 'Morales Campos', 'F', 1300, [0.9, 1.0], true],
            ['Dylan', 'Aguilar Soto', 'M', 1500, [-0.2, -0.3], false],
            ['Ximena', 'Ortiz Navarro', 'F', 1700, [0.4], true],
            ['Adrián', 'Silva Herrera', 'M', 2200, [0.0], true],
            ['Daniela', 'Peña Ramírez', 'F', 2900, [0.0], true],
            ['Lucas', 'Gómez Vásquez', 'M', 3500, [0.0], false],
            ['Sofía', 'Delgado Cárdenas', 'F', 4500, [0.0], true],
        ];

        $nombresTutores = [['María', 'Rosa'], ['Carmen', 'Lucía'], ['Ana', 'Julia'], ['Patricia', 'Elena'], ['Juan', 'Carlos'], ['Luis', 'Miguel'], ['Jorge', 'Pedro']];
        $dniBase = 70000000;

        foreach ($ninos as $i => [$nombres, $apellidos, $sexo, $edadDias, $trayectoria, $vacunasAlDia]) {
            $nacimiento = today()->subDays($edadDias);
            $madre = $nombresTutores[$i % 4];
            $tutor = [
                'dni' => (string) ($dniBase + $i * 7 + 1),
                'nombres' => $madre[0].' '.$madre[1],
                'apellidos' => explode(' ', $apellidos)[1].' '.['López', 'García', 'Pérez', 'Fernández'][$i % 4],
                'telefono' => '9'.str_pad((string) (10000000 + $i * 1234567 % 89999999), 8, '0', STR_PAD_LEFT),
                'email' => 'tutor'.($i + 1).'@correo.test',
                'direccion' => 'Jr. Amazonas '.(100 + $i * 10).', Chachapoyas',
            ];

            $paciente = $pacientes->registrar(
                ['nombres' => $nombres, 'apellidos' => $apellidos, 'sexo' => $sexo, 'fecha_nacimiento' => $nacimiento,
                    'dni' => $edadDias > 30 ? (string) (80000000 + $i * 13) : null, 'seguro' => ['SIS', 'EsSalud', 'Privado', 'Ninguno'][$i % 4],
                    'grupo_sanguineo' => ['O+', 'A+', 'B+', 'O+', 'AB+'][$i % 5], 'alergias' => $i === 6 ? 'Penicilina' : null,
                    'direccion' => $tutor['direccion']],
                $tutor,
                ['parentesco' => 'Madre'],
                ['acepta_tratamiento_datos' => true, 'acepta_atencion_medica' => true, 'acepta_notificaciones' => true],
            );

            // Segundo tutor (padre) en algunos pacientes
            if ($i % 3 === 0) {
                $padre = Tutor::create([
                    'dni' => (string) ($dniBase + $i * 7 + 2),
                    'nombres' => $nombresTutores[4 + $i % 3][0].' '.$nombresTutores[4 + $i % 3][1],
                    'apellidos' => $apellidos,
                    'telefono' => '98'.str_pad((string) (1000000 + $i * 7654321 % 8999999), 7, '0', STR_PAD_LEFT),
                    'email' => null,
                ]);
                $pacientes->vincular($paciente, $padre, 'Padre', false, true);
            }

            // Ficha clínica
            $paciente->pediatricRecord()->create([
                'numero_controles_prenatales' => 6 + $i % 4, 'edad_materna' => 22 + $i % 15,
                'patologias_embarazo' => $i % 5 === 0 ? 'Anemia leve en el 2do trimestre' : 'Ninguna',
                'suplemento_hierro_acido_folico' => true, 'edad_gestacional_semanas' => $i === 3 ? 35 : 38 + $i % 3,
                'tipo_parto' => ['eutocico', 'cesarea'][$i % 2], 'peso_nacer_kg' => round(2.9 + ($i % 7) * 0.12, 2),
                'talla_nacer_cm' => 48 + $i % 4, 'perimetro_cefalico_nacer_cm' => 33.5 + ($i % 3) * 0.5,
                'apgar_1' => 8, 'apgar_5' => 9, 'complicaciones_parto' => 'Ninguna', 'lactancia' => ['exclusiva', 'mixta', 'formula'][$i % 3],
                'tamizaje_neonatal' => true, 'hospitalizaciones' => 'Ninguna', 'enfermedades_previas' => $i % 4 === 0 ? 'IRA a los 6 meses' : 'Ninguna',
                'antecedentes_familiares' => $i % 6 === 0 ? 'Abuelo con diabetes tipo 2' : 'Ninguno', 'registrado_por' => $enfermera->id,
            ]);

            // Mediciones: una por control, espaciadas, siguiendo la trayectoria de Z
            $controles = count($trayectoria);
            $previas = [];
            foreach ($trayectoria as $k => $z) {
                $diasControl = $controles === 1 ? $edadDias : (int) round($edadDias * ($k + 1) / $controles);
                $fecha = $nacimiento->copy()->addDays($diasControl);
                if ($diasControl > PercentilOmsService::EDAD_MAXIMA_DIAS) {
                    $peso = round(15 + $diasControl / 365 * 2.4 + mt_rand(-10, 10) / 10, 1);
                    $talla = round(100 + ($diasControl / 365 - 4) * 6 + mt_rand(-20, 20) / 10, 1);
                    $pc = null;
                } else {
                    $peso = round($oms->valorParaZ('wfa', $sexo, $diasControl, $z), 2);
                    $talla = round($oms->valorParaZ('lhfa', $sexo, $diasControl, max(-3, $z * 0.8)), 1);
                    $pc = round($oms->valorParaZ('hcfa', $sexo, $diasControl, 0.1), 1);
                }
                $previas[] = $this->medir($paciente, $oms, $fecha, $peso, $talla, $pc, $previas, $enfermera->id);
            }

            // Vacunas: aplica todas las dosis cuya edad ya pasó (salvo los que no están al día)
            auth()->setUser($enfermera);
            $dosis = VaccineScheduleDose::with('vaccine.medication')->where('edad_dias', '<=', $edadDias)->orderBy('orden')->get();
            foreach ($dosis as $n => $d) {
                if (! $vacunasAlDia && $n >= max(2, (int) floor($dosis->count() / 2))) {
                    break;
                }
                $lote = $inventario->sugerirLote($d->vaccine->medication);
                if (! $lote) {
                    continue;
                }
                $vacunas->aplicar($paciente, $d, $lote, $enfermera, [
                    'fecha_aplicacion' => $nacimiento->copy()->addDays($d->edad_dias + mt_rand(0, 6))->min(today()),
                    'sitio_aplicacion' => $d->vaccine->via === 'Oral' ? 'Oral' : 'Muslo derecho',
                ]);
            }
            auth()->setUser(User::where('email', 'recepcion@clinica.test')->first());

            // Hitos del desarrollo
            $meses = (int) floor($edadDias / 30.4375);
            foreach (Milestone::where('edad_esperada_meses', '<=', $meses)->get() as $hito) {
                $logrado = ! ($i === 8 && $hito->dominio === 'lenguaje');
                MilestoneAssessment::create([
                    'patient_id' => $paciente->id, 'milestone_id' => $hito->id, 'fecha' => today()->subDays(mt_rand(0, 20)),
                    'logrado' => $logrado, 'alerta' => ! $logrado && $meses > $hito->edad_limite_meses, 'evaluado_por' => $enfermera->id,
                ]);
            }
        }

        // ESAVI de ejemplo
        $inm = Immunization::with(['patient', 'vaccine', 'lot'])->whereHas('vaccine', fn ($q) => $q->where('codigo', 'PENTA'))->first();
        if ($inm) {
            $inm->esaviReports()->create([
                'patient_id' => $inm->patient_id, 'fecha_inicio_sintomas' => $inm->fecha_aplicacion->copy()->setTime(20, 0),
                'sintomas' => 'Fiebre de 38.5 °C, irritabilidad y eritema en sitio de aplicación', 'gravedad' => 'leve',
                'descripcion' => 'Evento leve esperado, cede con paracetamol.', 'tratamiento' => 'Paracetamol 15 mg/kg/dosis', 'desenlace' => 'recuperado',
                'notificado_at' => now(), 'reportado_por' => $enfermera->id,
            ]);
        }

        // Cuenta de portal para el primer tutor (para pruebas del Módulo 5)
        $tutorPortal = Patient::where('nombres', 'Camila')->first()->tutors()->first();
        $usuario = User::updateOrCreate(['email' => 'tutor@clinica.test'], [
            'name' => $tutorPortal->nombre_completo, 'password' => UsuariosSeeder::DEMO_PASSWORD, 'telefono' => $tutorPortal->telefono, 'activo' => true,
        ]);
        $usuario->syncRoles(['tutor']);
        $tutorPortal->update(['user_id' => $usuario->id]);
        // El mismo tutor también es responsable de otro niño (visibilidad familiar)
        $hermano = Patient::where('nombres', 'Mía')->first();
        $hermano->tutors()->attach($tutorPortal->id, ['parentesco' => 'Madre', 'es_principal' => false, 'puede_retirar_medicamentos' => true]);
        $hermano->consents()->create(['tutor_id' => $tutorPortal->id, 'version' => '1.0', 'acepta_tratamiento_datos' => true,
            'acepta_atencion_medica' => true, 'acepta_notificaciones' => true, 'aceptado_at' => now()]);

        auth()->logout();
    }

    private function medir(Patient $p, PercentilOmsService $oms, Carbon $fecha, float $peso, float $talla, ?float $pc, array $previas, int $userId): AnthropometricMeasurement
    {
        $e = $oms->evaluar($p, $fecha, $peso, $talla, $pc, $previas);
        $relevantes = array_filter($e['alertas'], fn ($a) => $a['tipo'] !== 'info');

        return AnthropometricMeasurement::create([
            'patient_id' => $p->id, 'fecha' => $fecha, 'edad_dias' => $e['edad_dias'], 'peso_kg' => $peso, 'talla_cm' => $talla,
            'perimetro_cefalico_cm' => $pc, 'imc' => $e['imc'],
            'z_peso_edad' => $e['z_peso_edad'] ?? null, 'p_peso_edad' => $e['p_peso_edad'] ?? null,
            'z_talla_edad' => $e['z_talla_edad'] ?? null, 'p_talla_edad' => $e['p_talla_edad'] ?? null,
            'z_pc_edad' => $e['z_pc_edad'] ?? null, 'p_pc_edad' => $e['p_pc_edad'] ?? null,
            'z_imc_edad' => $e['z_imc_edad'] ?? null, 'p_imc_edad' => $e['p_imc_edad'] ?? null,
            'alertas' => $e['alertas'], 'tiene_alerta' => $relevantes !== [], 'registrado_por' => $userId,
        ]);
    }
}
