<?php

use App\Http\Controllers\Portal\NotificacionController;
use App\Http\Controllers\Portal\PortalController;
use Illuminate\Support\Facades\Route;

// Módulo 5 — Portal de tutores
Route::middleware('permission:portal.acceder')->prefix('portal')->group(function () {
    Route::get('/', [PortalController::class, 'index'])->name('portal.index');
    Route::get('hijos/{paciente}', [PortalController::class, 'hijo'])->name('portal.hijo');
    Route::get('recetas/{receta}/pdf', [PortalController::class, 'receta'])->name('portal.receta');
    Route::get('resultados/{orden}', [PortalController::class, 'resultado'])->name('portal.resultado');
    Route::get('resultados/{orden}/pdf', [PortalController::class, 'resultadoPdf'])->name('portal.resultado.pdf');
    Route::get('resultados/archivo/{item}', [PortalController::class, 'archivo'])->name('portal.archivo');
});

Route::middleware('permission:notificaciones.enviar')->group(function () {
    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/prueba-sms', [NotificacionController::class, 'probarSms'])->name('notificaciones.prueba');
});
