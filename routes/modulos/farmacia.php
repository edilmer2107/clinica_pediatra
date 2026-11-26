<?php

use App\Http\Controllers\Farmacia\ConciliacionController;
use App\Http\Controllers\Farmacia\ControladoController;
use App\Http\Controllers\Farmacia\DispensacionController;
use App\Http\Controllers\Farmacia\ErrorMedicacionController;
use App\Http\Controllers\Farmacia\LoteController;
use App\Http\Controllers\Farmacia\MedicamentoController;
use App\Http\Controllers\Farmacia\RetiroController;
use Illuminate\Support\Facades\Route;

// Módulo 3 — Farmacia e Inventario
Route::prefix('farmacia')->group(function () {
    Route::middleware('permission:inventario.ver')->group(function () {
        Route::get('medicamentos', [MedicamentoController::class, 'index'])->name('medicamentos.index');
        Route::get('lotes', [LoteController::class, 'index'])->name('lotes.index');
    });

    Route::middleware('permission:inventario.gestionar')->group(function () {
        Route::get('medicamentos/crear', [MedicamentoController::class, 'create'])->name('medicamentos.create');
        Route::post('medicamentos', [MedicamentoController::class, 'store'])->name('medicamentos.store');
        Route::get('medicamentos/{medicamento}/editar', [MedicamentoController::class, 'edit'])->name('medicamentos.edit');
        Route::put('medicamentos/{medicamento}', [MedicamentoController::class, 'update'])->name('medicamentos.update');

        Route::get('lotes/ingresar', [LoteController::class, 'create'])->name('lotes.create');
        Route::post('lotes', [LoteController::class, 'store'])->name('lotes.store');
        Route::patch('lotes/{lote}/estado', [LoteController::class, 'updateEstado'])->name('lotes.estado');

        Route::get('retiros', [RetiroController::class, 'index'])->name('retiros.index');
        Route::get('retiros/lote/{lote}', [RetiroController::class, 'show'])->name('retiros.show');
        Route::post('retiros/lote/{lote}', [RetiroController::class, 'store'])->name('retiros.store');

        Route::get('conciliaciones', [ConciliacionController::class, 'index'])->name('conciliaciones.index');
        Route::post('conciliaciones', [ConciliacionController::class, 'store'])->name('conciliaciones.store');
        Route::get('conciliaciones/{conciliacion}', [ConciliacionController::class, 'show'])->name('conciliaciones.show');
        Route::put('conciliaciones/{conciliacion}', [ConciliacionController::class, 'update'])->name('conciliaciones.update');

        Route::get('errores', [ErrorMedicacionController::class, 'index'])->name('errores.index');
        Route::get('errores/registrar', [ErrorMedicacionController::class, 'create'])->name('errores.create');
        Route::post('errores', [ErrorMedicacionController::class, 'store'])->name('errores.store');
        Route::get('errores/{error}/evidencia', [ErrorMedicacionController::class, 'evidencia'])->name('errores.evidencia');
    });

    Route::get('medicamentos/{medicamento}', [MedicamentoController::class, 'show'])
        ->middleware('permission:inventario.ver')->name('medicamentos.show');

    Route::middleware('permission:dispensacion.realizar')->group(function () {
        Route::get('dispensaciones', [DispensacionController::class, 'index'])->name('dispensaciones.index');
        Route::get('dispensaciones/receta/{receta}', [DispensacionController::class, 'create'])->name('dispensaciones.create');
        Route::post('dispensaciones/receta/{receta}', [DispensacionController::class, 'store'])->name('dispensaciones.store');
        Route::post('dispensaciones/receta/{receta}/lista', [DispensacionController::class, 'listaParaRetiro'])->name('dispensaciones.lista');
        Route::get('dispensaciones/{dispensacion}', [DispensacionController::class, 'show'])->name('dispensaciones.show');
    });

    Route::get('controlados', [ControladoController::class, 'index'])
        ->middleware('permission:controlados.gestionar')->name('controlados.index');
});
