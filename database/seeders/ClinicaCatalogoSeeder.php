<?php

namespace Database\Seeders;

use App\Models\Cie10Code;
use App\Models\DrugInteraction;
use App\Models\LabTest;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ClinicaCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $cie10 = [
            ['J00', 'Rinofaringitis aguda (resfriado común)'], ['J02.9', 'Faringitis aguda, no especificada'], ['J03.9', 'Amigdalitis aguda, no especificada'],
            ['H66.9', 'Otitis media, no especificada'], ['J06.9', 'Infección aguda de las vías respiratorias superiores'], ['J18.9', 'Neumonía, no especificada'],
            ['J21.9', 'Bronquiolitis aguda, no especificada'], ['J45.9', 'Asma, no especificada'], ['A09', 'Diarrea y gastroenteritis de presunto origen infeccioso'],
            ['K59.0', 'Estreñimiento'], ['B82.9', 'Parasitosis intestinal, sin otra especificación'], ['D50.9', 'Anemia por deficiencia de hierro'],
            ['E44.0', 'Desnutrición proteicocalórica moderada'], ['E66.9', 'Obesidad, no especificada'], ['L20.9', 'Dermatitis atópica'],
            ['L22', 'Dermatitis del pañal'], ['B37.0', 'Estomatitis candidiásica (muguet)'], ['N39.0', 'Infección de vías urinarias, sitio no especificado'],
            ['R50.9', 'Fiebre, no especificada'], ['R62.0', 'Retardo del desarrollo'], ['F90.0', 'Trastorno de la actividad y de la atención'],
            ['P59.9', 'Ictericia neonatal, no especificada'], ['H10.9', 'Conjuntivitis, no especificada'], ['T78.4', 'Alergia no especificada'],
            ['G40.9', 'Epilepsia, tipo no especificado'], ['R56.0', 'Convulsiones febriles'], ['Z00.1', 'Control de salud de rutina del niño'],
            ['Z23', 'Necesidad de inmunización'], ['B01.9', 'Varicela sin complicaciones'], ['B08.4', 'Enfermedad de mano, pie y boca'],
        ];
        foreach ($cie10 as [$codigo, $desc]) {
            Cie10Code::updateOrCreate(['codigo' => $codigo], ['descripcion' => $desc]);
        }

        $servicios = [
            ['CON-GEN', 'Consulta pediátrica general', 'consulta', 60], ['CON-CRED', 'Control de crecimiento y desarrollo (niño sano)', 'consulta', 50],
            ['CON-TELE', 'Teleconsulta pediátrica', 'consulta', 45], ['PRO-NEB', 'Nebulización', 'procedimiento', 25],
            ['PRO-CUR', 'Curación simple', 'procedimiento', 30], ['PRO-INY', 'Aplicación de inyectable', 'procedimiento', 10],
            ['PRO-LAV', 'Lavado de oídos', 'procedimiento', 35], ['PRO-SUT', 'Sutura menor', 'procedimiento', 80],
        ];
        foreach ($servicios as [$codigo, $nombre, $tipo, $precio]) {
            Service::updateOrCreate(['codigo' => $codigo], ['nombre' => $nombre, 'tipo' => $tipo, 'precio' => $precio, 'activo' => true]);
        }

        $examenes = [
            ['LAB-HB', 'Hemoglobina', 'laboratorio', 'g/dL', 11.0, 14.5, 15], ['LAB-LEU', 'Leucocitos', 'laboratorio', 'x10³/µL', 5.0, 15.0, 15],
            ['LAB-PLQ', 'Plaquetas', 'laboratorio', 'x10³/µL', 150, 450, 15], ['LAB-GLU', 'Glucosa en ayunas', 'laboratorio', 'mg/dL', 60, 100, 12],
            ['LAB-PCR', 'Proteína C reactiva', 'laboratorio', 'mg/L', 0, 5, 30], ['LAB-FER', 'Ferritina sérica', 'laboratorio', 'ng/mL', 12, 150, 45],
            ['LAB-TSH', 'TSH', 'laboratorio', 'µUI/mL', 0.7, 6.0, 40], ['LAB-ORI', 'Leucocitos en orina (sedimento)', 'laboratorio', 'xc', 0, 5, 18],
            ['LAB-PAR', 'Examen parasitológico seriado', 'laboratorio', null, null, null, 20],
            ['IMG-RXT', 'Radiografía de tórax', 'imagen', null, null, null, 60], ['IMG-ECA', 'Ecografía abdominal', 'imagen', null, null, null, 90],
            ['IMG-ECC', 'Ecografía de caderas', 'imagen', null, null, null, 90],
        ];
        foreach ($examenes as [$codigo, $nombre, $tipo, $unidad, $min, $max, $precio]) {
            LabTest::updateOrCreate(['codigo' => $codigo], ['nombre' => $nombre, 'tipo' => $tipo, 'unidad' => $unidad, 'ref_min' => $min, 'ref_max' => $max, 'precio' => $precio]);
        }

        // Interacciones medicamentosas relevantes en pediatría (referenciales)
        $interacciones = [
            ['claritromicina', 'ondansetron', 'grave', 'Riesgo de prolongación del intervalo QT y arritmias.'],
            ['azitromicina', 'ondansetron', 'moderada', 'Posible prolongación del intervalo QT; monitorizar.'],
            ['claritromicina', 'fluconazol', 'moderada', 'Aumento de niveles plasmáticos y riesgo de QT prolongado.'],
            ['diazepam', 'fenobarbital', 'grave', 'Depresión aditiva del sistema nervioso central y respiratoria.'],
            ['clonazepam', 'fenobarbital', 'grave', 'Depresión aditiva del sistema nervioso central y respiratoria.'],
            ['codeina', 'diazepam', 'grave', 'Depresión respiratoria grave (opioide + benzodiacepina).'],
            ['diazepam', 'morfina', 'grave', 'Depresión respiratoria grave (opioide + benzodiacepina).'],
            ['clonazepam', 'codeina', 'grave', 'Depresión respiratoria grave (opioide + benzodiacepina).'],
            ['ibuprofeno', 'prednisolona', 'moderada', 'Mayor riesgo de lesión gastrointestinal.'],
            ['fenobarbital', 'metronidazol', 'moderada', 'El fenobarbital reduce la eficacia del metronidazol.'],
        ];
        foreach ($interacciones as [$a, $b, $sev, $desc]) {
            [$a, $b] = $a < $b ? [$a, $b] : [$b, $a];
            DrugInteraction::updateOrCreate(['principio_a' => $a, 'principio_b' => $b], ['severidad' => $sev, 'descripcion' => $desc]);
        }
    }
}
