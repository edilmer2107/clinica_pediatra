<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

// Autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/panel', DashboardController::class)->name('dashboard');

    require __DIR__.'/modulos/farmacia.php';
    require __DIR__.'/modulos/pacientes.php';
    require __DIR__.'/modulos/clinica.php';
    require __DIR__.'/modulos/facturacion.php';
    require __DIR__.'/modulos/portal.php';
    require __DIR__.'/modulos/reportes.php';
});

// Seguimiento de apertura de notificaciones (enlace público con token aleatorio)
require __DIR__.'/modulos/publico.php';
