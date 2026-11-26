<?php

namespace Database\Seeders;

use App\Models\Medication;
use App\Models\Milestone;
use App\Models\Vaccine;
use App\Models\VaccinationGoal;
use App\Models\VaccineScheduleDose;
use Illuminate\Database\Seeder;

/**
 * Esquema regular de vacunación (referencial, basado en la NTS de inmunizaciones del MINSA),
 * hitos del desarrollo psicomotor y metas anuales de cobertura.
 */
class PacientesCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        // código, nombre, enfermedades, vía, CVX, código del producto en farmacia
        $vacunas = [
            ['BCG', 'BCG', 'Tuberculosis (formas graves)', 'ID', '19', 'VAC-BCG'],
            ['HVB', 'Hepatitis B pediátrica', 'Hepatitis B', 'IM', '08', 'VAC-HVB'],
            ['PENTA', 'Pentavalente', 'Difteria, tos ferina, tétanos, hepatitis B, Haemophilus influenzae b', 'IM', '102', 'VAC-PENTA'],
            ['IPV', 'Antipolio inactivada (IPV)', 'Poliomielitis', 'IM', '10', 'VAC-IPV'],
            ['APO', 'Antipolio oral (bOPV)', 'Poliomielitis', 'Oral', '182', 'VAC-APO'],
            ['ROTA', 'Rotavirus', 'Diarrea por rotavirus', 'Oral', '122', 'VAC-ROTA'],
            ['NEUMO', 'Neumococo conjugada', 'Neumonía, otitis, meningitis neumocócica', 'IM', '133', 'VAC-NEUMO'],
            ['INFLU', 'Influenza pediátrica', 'Influenza estacional', 'IM', '141', 'VAC-INFLU'],
            ['SPR', 'SPR', 'Sarampión, paperas, rubéola', 'SC', '03', 'VAC-SPR'],
            ['VARI', 'Varicela', 'Varicela', 'SC', '21', 'VAC-VARI'],
            ['AMA', 'Antiamarílica', 'Fiebre amarilla', 'SC', '37', 'VAC-AMA'],
            ['HVA', 'Hepatitis A', 'Hepatitis A', 'IM', '83', 'VAC-HVA'],
            ['DPT', 'DPT', 'Difteria, tos ferina, tétanos', 'IM', '01', 'VAC-DPT'],
            ['VPH', 'Virus del papiloma humano', 'Infección por VPH', 'IM', '165', 'VAC-VPH'],
        ];
        foreach ($vacunas as [$codigo, $nombre, $enf, $via, $cvx, $producto]) {
            Vaccine::updateOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre, 'enfermedades' => $enf, 'via' => $via, 'codigo_cvx' => $cvx,
                'medication_id' => Medication::where('codigo', $producto)->value('id'), 'precio' => 20,
            ]);
        }

        // vacuna, n° dosis, nombre de dosis, edad (días), tolerancia (días), grupo etario
        $esquema = [
            ['BCG', 1, 'Dosis única (RN)', 0, 365, 'Recién nacido'],
            ['HVB', 1, 'Dosis RN', 0, 7, 'Recién nacido'],
            ['PENTA', 1, '1ra dosis (2 m)', 60, 30, 'Menor de 1 año'],
            ['IPV', 1, '1ra dosis (2 m)', 60, 30, 'Menor de 1 año'],
            ['ROTA', 1, '1ra dosis (2 m)', 60, 30, 'Menor de 1 año'],
            ['NEUMO', 1, '1ra dosis (2 m)', 60, 30, 'Menor de 1 año'],
            ['PENTA', 2, '2da dosis (4 m)', 120, 30, 'Menor de 1 año'],
            ['IPV', 2, '2da dosis (4 m)', 120, 30, 'Menor de 1 año'],
            ['ROTA', 2, '2da dosis (4 m)', 120, 30, 'Menor de 1 año'],
            ['NEUMO', 2, '2da dosis (4 m)', 120, 30, 'Menor de 1 año'],
            ['PENTA', 3, '3ra dosis (6 m)', 180, 30, 'Menor de 1 año'],
            ['APO', 1, '3ra dosis polio (6 m)', 180, 30, 'Menor de 1 año'],
            ['INFLU', 1, '1ra dosis (6 m)', 180, 30, 'Menor de 1 año'],
            ['INFLU', 2, '2da dosis (7 m)', 210, 30, 'Menor de 1 año'],
            ['SPR', 1, '1ra dosis (12 m)', 365, 60, '1 año'],
            ['NEUMO', 3, 'Refuerzo (12 m)', 365, 60, '1 año'],
            ['VARI', 1, 'Dosis (12 m)', 365, 60, '1 año'],
            ['AMA', 1, 'Dosis única (15 m)', 456, 60, '1 año'],
            ['HVA', 1, 'Dosis (15 m)', 456, 60, '1 año'],
            ['DPT', 1, '1er refuerzo (18 m)', 548, 60, '1 año'],
            ['APO', 2, '1er refuerzo (18 m)', 548, 60, '1 año'],
            ['SPR', 2, '2da dosis (18 m)', 548, 60, '1 año'],
            ['DPT', 2, '2do refuerzo (4 a)', 1461, 365, '4 años'],
            ['APO', 3, '2do refuerzo (4 a)', 1461, 365, '4 años'],
            ['VPH', 1, 'Dosis (9 a)', 3287, 365, '9 a 13 años'],
        ];
        foreach ($esquema as $orden => [$codigo, $num, $nombreDosis, $edad, $tol, $grupo]) {
            $vacuna = Vaccine::where('codigo', $codigo)->first();
            VaccineScheduleDose::updateOrCreate(
                ['vaccine_id' => $vacuna->id, 'numero_dosis' => $num],
                ['nombre_dosis' => $nombreDosis, 'edad_dias' => $edad, 'tolerancia_dias' => $tol, 'grupo_edad' => $grupo, 'orden' => $orden + 1],
            );
        }

        // Metas anuales de cobertura por vacuna y grupo etario (población asignada ficticia)
        $anio = (int) now()->year;
        foreach (VaccineScheduleDose::with('vaccine')->get()->groupBy(fn ($d) => $d->vaccine_id.'|'.$d->grupo_edad) as $grupo) {
            $d = $grupo->first();
            VaccinationGoal::updateOrCreate(
                ['vaccine_id' => $d->vaccine_id, 'grupo_edad' => $d->grupo_edad, 'anio' => $anio],
                ['meta' => match ($d->grupo_edad) { 'Recién nacido' => 20, 'Menor de 1 año' => 25 * $grupo->count(), '1 año' => 20 * $grupo->count(), default => 15 }],
            );
        }

        // Hitos del desarrollo: dominio, descripción, edad esperada (m), edad límite (m)
        $hitos = [
            ['motricidad_gruesa', 'Sostiene la cabeza en prono', 2, 4],
            ['motricidad_gruesa', 'Se sienta sin apoyo', 6, 9],
            ['motricidad_gruesa', 'Gatea', 9, 12],
            ['motricidad_gruesa', 'Camina solo(a)', 12, 18],
            ['motricidad_gruesa', 'Sube escaleras con apoyo', 18, 24],
            ['motricidad_gruesa', 'Salta con ambos pies', 24, 30],
            ['motricidad_gruesa', 'Se para en un pie 3 segundos', 36, 48],
            ['motricidad_fina', 'Lleva objetos a la boca', 4, 6],
            ['motricidad_fina', 'Pasa objetos de una mano a otra', 6, 9],
            ['motricidad_fina', 'Pinza fina (pulgar-índice)', 9, 12],
            ['motricidad_fina', 'Hace torre de 3 cubos', 18, 24],
            ['motricidad_fina', 'Copia un círculo', 36, 42],
            ['motricidad_fina', 'Dibuja figura humana de 3 partes', 48, 60],
            ['lenguaje', 'Vocaliza sonidos (gorjeos)', 2, 4],
            ['lenguaje', 'Balbucea sílabas (ma-ma, pa-pa)', 6, 9],
            ['lenguaje', 'Dice primeras palabras con sentido', 12, 15],
            ['lenguaje', 'Usa al menos 20 palabras', 18, 24],
            ['lenguaje', 'Une dos palabras en frases', 24, 30],
            ['lenguaje', 'Dice su nombre completo', 36, 48],
            ['lenguaje', 'Cuenta una historia sencilla', 48, 60],
            ['social', 'Sonrisa social', 2, 3],
            ['social', 'Reconoce a sus padres', 4, 6],
            ['social', 'Extraña a personas desconocidas', 8, 12],
            ['social', 'Juega a las escondidas (cucú)', 9, 12],
            ['social', 'Imita tareas del hogar', 18, 24],
            ['social', 'Juega con otros niños', 36, 48],
            ['social', 'Se viste solo(a)', 48, 60],
        ];
        foreach ($hitos as [$dom, $desc, $esp, $lim]) {
            Milestone::updateOrCreate(['dominio' => $dom, 'descripcion' => $desc], ['edad_esperada_meses' => $esp, 'edad_limite_meses' => $lim]);
        }
    }
}
