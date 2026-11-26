<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos granulares (RF Módulo 4):
 * - Farmacéutico no modifica historia clínica.
 * - Médico no altera inventario.
 * - Facturación solo visualiza cargos y pagos.
 */
class RolesPermisosSeeder extends Seeder
{
    public const PERMISOS = [
        // Módulo 1
        'pacientes.ver', 'pacientes.gestionar',
        'ficha.ver', 'ficha.editar',
        'mediciones.registrar',
        'vacunas.ver', 'vacunas.aplicar', 'esavi.registrar',
        // Módulo 2
        'citas.ver', 'citas.gestionar',
        'consultas.ver', 'consultas.gestionar',
        'recetas.ver', 'recetas.emitir',
        'laboratorio.solicitar', 'laboratorio.resultados',
        // Módulo 3
        'inventario.ver', 'inventario.gestionar',
        'dispensacion.realizar', 'controlados.gestionar',
        // Módulo 4
        'facturacion.ver', 'facturacion.gestionar', 'facturacion.anular',
        'usuarios.gestionar', 'auditoria.ver',
        // Reportes
        'reportes.clinicos', 'reportes.farmacia', 'reportes.financieros', 'reportes.comunicacion',
        // Transversal / Módulo 5
        'fhir.exportar', 'fhir.importar', 'notificaciones.enviar',
        'portal.acceder',
    ];

    public const ROLES = [
        'administrador' => '*',
        'medico' => [
            'pacientes.ver', 'ficha.ver', 'ficha.editar', 'mediciones.registrar',
            'vacunas.ver', 'esavi.registrar', 'citas.ver',
            'consultas.ver', 'consultas.gestionar', 'recetas.ver', 'recetas.emitir',
            'laboratorio.solicitar', 'laboratorio.resultados',
            'inventario.ver', 'reportes.clinicos', 'fhir.exportar',
        ],
        'enfermeria' => [
            'pacientes.ver', 'ficha.ver', 'mediciones.registrar',
            'vacunas.ver', 'vacunas.aplicar', 'esavi.registrar',
            'citas.ver', 'laboratorio.resultados', 'reportes.clinicos', 'notificaciones.enviar',
        ],
        'recepcion' => [
            'pacientes.ver', 'pacientes.gestionar', 'vacunas.ver',
            'citas.ver', 'citas.gestionar', 'notificaciones.enviar',
        ],
        'farmaceutico' => [
            'pacientes.ver', 'recetas.ver',
            'inventario.ver', 'inventario.gestionar',
            'dispensacion.realizar', 'controlados.gestionar', 'reportes.farmacia', 'notificaciones.enviar',
        ],
        'facturacion' => [
            'pacientes.ver', 'facturacion.ver', 'facturacion.gestionar', 'reportes.financieros',
        ],
        'tutor' => ['portal.acceder'],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISOS as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        foreach (self::ROLES as $rol => $permisos) {
            $role = Role::findOrCreate($rol, 'web');
            $role->syncPermissions($permisos === '*' ? self::PERMISOS : $permisos);
        }
    }
}
