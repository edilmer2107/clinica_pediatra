<?php

namespace Database\Seeders;

use App\Models\Lot;
use App\Models\Medication;
use App\Models\User;
use App\Services\Farmacia\ControladosService;
use App\Services\Farmacia\InventarioService;
use Illuminate\Database\Seeder;

/**
 * Catálogo de medicamentos pediátricos y vacunas con lotes de prueba.
 * Rangos de dosis referenciales (formulario pediátrico), solo para fines académicos.
 */
class FarmaciaSeeder extends Seeder
{
    public function run(InventarioService $inventario, ControladosService $controlados): void
    {
        // código, nombre, principio, concentración, forma, presentación, unidad, mg/unidad, mg/mL, min, max mg/kg/día, máx mg/día, precio, stock mín, controlado
        $medicamentos = [
            ['MED-001', 'Amoxicilina', 'amoxicilina', '250 mg/5 mL', 'Suspensión oral', 'Frasco 60 mL', 'frasco', 3000, 50, 40, 90, 3000, 12.50, 10, null],
            ['MED-002', 'Paracetamol', 'paracetamol', '120 mg/5 mL', 'Jarabe', 'Frasco 60 mL', 'frasco', 1440, 24, 40, 75, 4000, 6.00, 15, null],
            ['MED-003', 'Paracetamol gotas', 'paracetamol', '100 mg/mL', 'Gotas orales', 'Frasco 15 mL', 'frasco', 1500, 100, 40, 75, 4000, 7.50, 10, null],
            ['MED-004', 'Ibuprofeno', 'ibuprofeno', '100 mg/5 mL', 'Suspensión oral', 'Frasco 60 mL', 'frasco', 1200, 20, 20, 40, 2400, 8.00, 10, null],
            ['MED-005', 'Azitromicina', 'azitromicina', '200 mg/5 mL', 'Suspensión oral', 'Frasco 15 mL', 'frasco', 600, 40, 10, 12, 500, 18.00, 5, null],
            ['MED-006', 'Cefalexina', 'cefalexina', '250 mg/5 mL', 'Suspensión oral', 'Frasco 60 mL', 'frasco', 3000, 50, 25, 100, 4000, 15.00, 5, null],
            ['MED-007', 'Claritromicina', 'claritromicina', '250 mg/5 mL', 'Suspensión oral', 'Frasco 60 mL', 'frasco', 3000, 50, 15, 15, 1000, 28.00, 5, null],
            ['MED-008', 'Prednisolona', 'prednisolona', '15 mg/5 mL', 'Jarabe', 'Frasco 60 mL', 'frasco', 180, 3, 1, 2, 60, 22.00, 5, null],
            ['MED-009', 'Salbutamol', 'salbutamol', '2 mg/5 mL', 'Jarabe', 'Frasco 120 mL', 'frasco', 48, 0.4, 0.3, 0.6, 8, 9.50, 5, null],
            ['MED-010', 'Cetirizina', 'cetirizina', '5 mg/5 mL', 'Jarabe', 'Frasco 60 mL', 'frasco', 60, 1, 0.25, 0.5, 10, 10.00, 5, null],
            ['MED-011', 'Sulfato ferroso', 'sulfato ferroso', '25 mg Fe/mL', 'Gotas orales', 'Frasco 30 mL', 'frasco', 750, 25, 3, 6, 60, 5.00, 10, null],
            ['MED-012', 'Metronidazol', 'metronidazol', '250 mg/5 mL', 'Suspensión oral', 'Frasco 120 mL', 'frasco', 6000, 50, 15, 50, 2000, 11.00, 5, null],
            ['MED-013', 'Ondansetrón', 'ondansetron', '4 mg/5 mL', 'Jarabe', 'Frasco 50 mL', 'frasco', 40, 0.8, 0.15, 0.45, 24, 35.00, 3, null],
            ['MED-014', 'Sales de rehidratación oral', 'sales de rehidratacion oral', '20.5 g', 'Polvo para solución', 'Sobre', 'sobre', null, null, null, null, null, 1.50, 30, null],
            ['MED-015', 'Fluconazol', 'fluconazol', '50 mg/5 mL', 'Suspensión oral', 'Frasco 35 mL', 'frasco', 350, 10, 3, 12, 400, 24.00, 3, null],
            ['MED-016', 'Fenobarbital', 'fenobarbital', '20 mg/5 mL', 'Elixir', 'Frasco 60 mL', 'frasco', 240, 4, 3, 6, 300, 14.00, 3, 'psicotropico'],
            ['MED-017', 'Diazepam', 'diazepam', '5 mg', 'Tableta', 'Caja x 30', 'tableta', 5, null, 0.12, 0.8, 30, 0.80, 20, 'psicotropico'],
            ['MED-018', 'Clonazepam gotas', 'clonazepam', '2.5 mg/mL', 'Gotas orales', 'Frasco 20 mL', 'frasco', 50, 2.5, 0.01, 0.2, 20, 19.00, 3, 'psicotropico'],
            ['MED-019', 'Morfina', 'morfina', '10 mg/mL', 'Solución inyectable', 'Ampolla 1 mL', 'ampolla', 10, 10, 0.4, 1.2, 60, 6.50, 10, 'estupefaciente'],
            ['MED-020', 'Codeína', 'codeina', '15 mg/5 mL', 'Jarabe', 'Frasco 100 mL', 'frasco', 300, 3, 1, 1.5, 60, 16.00, 3, 'estupefaciente'],
        ];

        foreach ($medicamentos as $m) {
            Medication::updateOrCreate(['codigo' => $m[0]], [
                'nombre' => $m[1], 'principio_activo' => $m[2], 'concentracion' => $m[3], 'forma_farmaceutica' => $m[4],
                'presentacion' => $m[5], 'unidad' => $m[6], 'mg_por_unidad' => $m[7], 'mg_por_ml' => $m[8],
                'dosis_min_mg_kg_dia' => $m[9], 'dosis_max_mg_kg_dia' => $m[10], 'dosis_max_diaria_mg' => $m[11],
                'precio_venta' => $m[12], 'stock_minimo' => $m[13],
                'es_controlado' => $m[14] !== null, 'tipo_control' => $m[14], 'es_vacuna' => false, 'activo' => true,
            ]);
        }

        // Vacunas como productos de farmacia (stock descontado al vacunar)
        $vacunas = [
            ['VAC-BCG', 'Vacuna BCG', 'Bacilo Calmette-Guérin', 'Vial multidosis'],
            ['VAC-HVB', 'Vacuna Hepatitis B pediátrica', 'Antígeno de superficie HB', 'Vial 0.5 mL'],
            ['VAC-PENTA', 'Vacuna Pentavalente', 'DPT-HvB-Hib', 'Vial 0.5 mL'],
            ['VAC-IPV', 'Vacuna Antipolio inactivada (IPV)', 'Poliovirus inactivado', 'Vial 0.5 mL'],
            ['VAC-APO', 'Vacuna Antipolio oral (bOPV)', 'Poliovirus atenuado', 'Gotero'],
            ['VAC-ROTA', 'Vacuna Rotavirus', 'Rotavirus atenuado', 'Dosis oral'],
            ['VAC-NEUMO', 'Vacuna Neumococo conjugada', 'Polisacárido neumocócico conjugado', 'Jeringa prellenada'],
            ['VAC-INFLU', 'Vacuna Influenza pediátrica', 'Virus influenza inactivado', 'Jeringa 0.25 mL'],
            ['VAC-SPR', 'Vacuna SPR', 'Sarampión-Paperas-Rubéola', 'Vial liofilizado'],
            ['VAC-VARI', 'Vacuna Varicela', 'Virus varicela atenuado', 'Vial liofilizado'],
            ['VAC-AMA', 'Vacuna Antiamarílica', 'Virus fiebre amarilla atenuado', 'Vial liofilizado'],
            ['VAC-HVA', 'Vacuna Hepatitis A', 'Virus hepatitis A inactivado', 'Jeringa prellenada'],
            ['VAC-DPT', 'Vacuna DPT', 'Difteria-Pertussis-Tétanos', 'Vial 0.5 mL'],
            ['VAC-VPH', 'Vacuna VPH', 'Virus del papiloma humano', 'Jeringa prellenada'],
        ];
        foreach ($vacunas as $v) {
            Medication::updateOrCreate(['codigo' => $v[0]], [
                'nombre' => $v[1], 'principio_activo' => $v[2], 'concentracion' => '1 dosis', 'forma_farmaceutica' => 'Vacuna',
                'presentacion' => $v[3], 'unidad' => 'dosis', 'precio_venta' => 0, 'stock_minimo' => 10,
                'es_controlado' => false, 'es_vacuna' => true, 'activo' => true,
            ]);
        }

        if (Lot::exists()) {
            return;
        }

        $farmaceutico = User::where('email', 'farmacia@clinica.test')->first();
        auth()->setUser($farmaceutico);

        $ubicaciones = ['Estante A-1', 'Estante A-2', 'Estante B-1', 'Estante B-2', 'Estante C-1', 'Refrigerador R-1'];
        $proveedores = ['Droguería Andina S.A.C.', 'Distribuidora Médica del Norte', 'Medifarma Perú S.A.'];
        $n = 0;

        foreach (Medication::all() as $med) {
            $n++;
            $ubic = $med->es_vacuna ? 'Refrigerador R-1' : $ubicaciones[$n % 5];
            // Lote vigente principal
            $lote = $inventario->registrarEntrada([
                'medication_id' => $med->id, 'numero_lote' => sprintf('L%s-%03d', now()->format('y'), $n * 10 + 1),
                'fecha_vencimiento' => today()->addMonths(10 + $n % 12), 'cantidad' => $med->es_vacuna ? 60 : 40 + ($n % 5) * 10,
                'costo_unitario' => round($med->precio_venta * 0.6, 2), 'almacen' => 'Almacén principal', 'ubicacion' => $ubic,
                'proveedor' => $proveedores[$n % 3], 'fecha_ingreso' => today()->subMonths(2),
            ]);
            $this->libro($controlados, $med, $lote, $farmaceutico);

            // Lote próximo a vencer (para FEFO y reporte de vencimientos)
            if ($n % 3 === 0 || $med->es_controlado) {
                $lote = $inventario->registrarEntrada([
                    'medication_id' => $med->id, 'numero_lote' => sprintf('L%s-%03d', now()->subYear()->format('y'), $n * 10 + 2),
                    'fecha_vencimiento' => today()->addDays(20 + $n * 3), 'cantidad' => 15,
                    'costo_unitario' => round($med->precio_venta * 0.6, 2), 'almacen' => 'Almacén principal', 'ubicacion' => $ubic,
                    'proveedor' => $proveedores[($n + 1) % 3], 'fecha_ingreso' => today()->subMonths(9),
                ]);
                $this->libro($controlados, $med, $lote, $farmaceutico);
            }
        }

        // Lote vencido (debe quedar bloqueado) y lote en cuarentena
        $amoxi = Medication::where('codigo', 'MED-001')->first();
        $vencido = $inventario->registrarEntrada([
            'medication_id' => $amoxi->id, 'numero_lote' => 'L24-VENC01', 'fecha_vencimiento' => today()->addDay(),
            'cantidad' => 8, 'costo_unitario' => 7.5, 'almacen' => 'Almacén principal', 'ubicacion' => 'Estante A-2',
            'proveedor' => $proveedores[0], 'fecha_ingreso' => today()->subYear(),
        ]);
        $vencido->update(['fecha_vencimiento' => today()->subDays(15)]);

        $ibu = Medication::where('codigo', 'MED-004')->first();
        $inventario->registrarEntrada([
            'medication_id' => $ibu->id, 'numero_lote' => 'L25-CUAR01', 'fecha_vencimiento' => today()->addMonths(8),
            'cantidad' => 20, 'costo_unitario' => 4.8, 'almacen' => 'Almacén principal', 'ubicacion' => 'Zona de cuarentena',
            'proveedor' => $proveedores[2], 'fecha_ingreso' => today()->subWeek(), 'estado' => 'cuarentena',
        ]);

        auth()->logout();
    }

    private function libro(ControladosService $controlados, Medication $med, Lot $lote, User $farmaceutico): void
    {
        if ($med->es_controlado) {
            $controlados->registrar($med, $lote, 'entrada', $lote->cantidad_inicial, $farmaceutico->id, ['documento' => 'Guía de remisión']);
        }
    }
}
