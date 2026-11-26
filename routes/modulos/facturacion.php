<?php

use App\Http\Controllers\Admin\AuditoriaController;
use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Controllers\Facturacion\CuentaController;
use App\Http\Controllers\Facturacion\FacturaController;
use Illuminate\Support\Facades\Route;

// Módulo 4 — Facturación y Administración
Route::middleware('permission:facturacion.ver')->group(function () {
    Route::get('cuentas', [CuentaController::class, 'index'])->name('cuentas.index');
    Route::get('cuentas/{cuenta}', [CuentaController::class, 'show'])->name('cuentas.show');
    Route::post('cuentas/{cuenta}/facturar', [CuentaController::class, 'facturar'])->name('cuentas.facturar');
    Route::get('facturas', [FacturaController::class, 'index'])->name('facturas.index');
    Route::get('facturas/{factura}', [FacturaController::class, 'show'])->name('facturas.show');
    Route::get('facturas/{factura}/pdf', [FacturaController::class, 'pdf'])->name('facturas.pdf');
    Route::post('facturas/{factura}/pagos', [FacturaController::class, 'pagar'])->name('facturas.pagar');
    Route::patch('facturas/{factura}/anular', [FacturaController::class, 'anular'])->name('facturas.anular');
});

Route::middleware('permission:usuarios.gestionar')->group(function () {
    Route::get('usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('usuarios/crear', [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::get('usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
});

Route::middleware('permission:auditoria.ver')->group(function () {
    Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
    Route::post('auditoria/verificar', [AuditoriaController::class, 'verificar'])->name('auditoria.verificar');
});

// Transversal — Interoperabilidad HL7 FHIR
Route::middleware('permission:fhir.exportar')->group(function () {
    Route::get('fhir', [\App\Http\Controllers\Admin\FhirController::class, 'index'])->name('fhir.index');
    Route::get('fhir/pacientes/{paciente}', [\App\Http\Controllers\Admin\FhirController::class, 'exportar'])->name('fhir.exportar');
});
Route::post('fhir/importar', [\App\Http\Controllers\Admin\FhirController::class, 'importar'])
    ->middleware('permission:fhir.importar')->name('fhir.importar');
