<?php

use App\Http\Controllers\Reportes\ReporteController;
use Illuminate\Support\Facades\Route;

Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
Route::get('reportes/{reporte}', [ReporteController::class, 'show'])
    ->where('reporte', '[a-z0-9\-]+')->name('reportes.show');
