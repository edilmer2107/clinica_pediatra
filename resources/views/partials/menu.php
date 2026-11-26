<?php

/**
 * Menú lateral: [sección => [[etiqueta, ruta, permiso, patrón activo]]]
 */
return [
    'General' => [
        ['Panel principal', 'dashboard', null, 'dashboard'],
    ],
    'Pacientes' => [
        ['Pacientes', 'pacientes.index', 'pacientes.ver', 'pacientes.*'],
        ['Tutores', 'tutores.index', 'pacientes.ver', 'tutores.*'],
        ['Vacunación', 'vacunacion.index', 'vacunas.ver', 'vacunacion.*'],
    ],
    'Atención clínica' => [
        ['Citas', 'citas.index', 'citas.ver', 'citas.*'],
        ['Consultas', 'consultas.index', 'consultas.ver', 'consultas.*'],
        ['Recetas', 'recetas.index', 'recetas.ver', 'recetas.*'],
        ['Laboratorio e imágenes', 'laboratorio.index', 'laboratorio.resultados', 'laboratorio.*'],
    ],
    'Farmacia' => [
        ['Medicamentos', 'medicamentos.index', 'inventario.ver', 'medicamentos.*'],
        ['Lotes y stock', 'lotes.index', 'inventario.ver', 'lotes.*'],
        ['Dispensación', 'dispensaciones.index', 'dispensacion.realizar', 'dispensaciones.*'],
        ['Libro de controlados', 'controlados.index', 'controlados.gestionar', 'controlados.*'],
        ['Retiros de mercado', 'retiros.index', 'inventario.gestionar', 'retiros.*'],
        ['Conciliación', 'conciliaciones.index', 'inventario.gestionar', 'conciliaciones.*'],
        ['Errores de medicación', 'errores.index', 'inventario.gestionar', 'errores.*'],
    ],
    'Facturación' => [
        ['Cuentas de pacientes', 'cuentas.index', 'facturacion.ver', 'cuentas.*'],
        ['Facturas', 'facturas.index', 'facturacion.ver', 'facturas.*'],
    ],
    'Comunicación' => [
        ['Notificaciones', 'notificaciones.index', 'notificaciones.enviar', 'notificaciones.*'],
    ],
    'Reportes' => [
        ['Reportes', 'reportes.index', null, 'reportes.*'],
    ],
    'Administración' => [
        ['Usuarios', 'usuarios.index', 'usuarios.gestionar', 'usuarios.*'],
        ['Bitácora de auditoría', 'auditoria.index', 'auditoria.ver', 'auditoria.*'],
        ['Interoperabilidad FHIR', 'fhir.index', 'fhir.exportar', 'fhir.*'],
    ],
];
