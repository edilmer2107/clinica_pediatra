<?php

use App\Http\Controllers\SeguimientoNotificacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/n/{token}.gif', [SeguimientoNotificacionController::class, 'pixel'])
        ->where('token', '[A-Za-z0-9]{40}')->name('notificaciones.pixel');
    Route::get('/n/{token}', [SeguimientoNotificacionController::class, 'abrir'])
        ->where('token', '[A-Za-z0-9]{40}')->name('notificaciones.abrir');
});
