<?php

use App\Http\Controllers\Clinica\CitaController;
use App\Http\Controllers\Clinica\ConsultaController;
use App\Http\Controllers\Clinica\LaboratorioController;
use App\Http\Controllers\Clinica\RecetaController;
use Illuminate\Support\Facades\Route;

// Módulo 2 — Gestión Clínica y Consultas
Route::middleware('permission:citas.ver')->group(function () {
    Route::get('citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('citas/agendar', [CitaController::class, 'create'])->name('citas.create');
    Route::post('citas', [CitaController::class, 'store'])->name('citas.store');
    Route::patch('citas/{cita}/cancelar', [CitaController::class, 'cancelar'])->name('citas.cancelar');
    Route::get('citas/{cita}/reprogramar', [CitaController::class, 'editReprogramar'])->name('citas.reprogramar.edit');
    Route::post('citas/{cita}/reprogramar', [CitaController::class, 'reprogramar'])->name('citas.reprogramar');
    Route::patch('citas/{cita}/confirmar', [CitaController::class, 'confirmar'])->name('citas.confirmar');
    Route::patch('citas/{cita}/no-asistio', [CitaController::class, 'noAsistio'])->name('citas.no_asistio');
    Route::post('citas/{cita}/recordatorio', [CitaController::class, 'recordatorio'])->name('citas.recordatorio');
});

Route::middleware('permission:consultas.ver')->group(function () {
    Route::get('consultas', [ConsultaController::class, 'index'])->name('consultas.index');
    Route::get('consultas/{consulta}', [ConsultaController::class, 'show'])->whereNumber('consulta')->name('consultas.show');
});
Route::middleware('permission:consultas.gestionar')->group(function () {
    Route::get('consultas/nueva', [ConsultaController::class, 'create'])->name('consultas.create');
    Route::post('consultas', [ConsultaController::class, 'store'])->name('consultas.store');
    Route::post('consultas/{consulta}/procedimientos', [ConsultaController::class, 'procedimiento'])->name('consultas.procedimiento');
    Route::patch('consultas/{consulta}/cerrar', [ConsultaController::class, 'cerrar'])->name('consultas.cerrar');
});

Route::middleware('permission:recetas.ver')->group(function () {
    Route::get('recetas', [RecetaController::class, 'index'])->name('recetas.index');
    Route::get('recetas/{receta}', [RecetaController::class, 'show'])->name('recetas.show');
    Route::get('recetas/{receta}/pdf', [RecetaController::class, 'pdf'])->name('recetas.pdf');
});
Route::middleware('permission:recetas.emitir')->group(function () {
    Route::post('consultas/{consulta}/recetas', [RecetaController::class, 'store'])->name('recetas.store');
    Route::post('consultas/{consulta}/recetas/calcular', [RecetaController::class, 'calcular'])->name('recetas.calcular');
    Route::put('recetas/items/{item}', [RecetaController::class, 'updateItem'])->name('recetas.items.update');
    Route::patch('recetas/{receta}/anular', [RecetaController::class, 'anular'])->name('recetas.anular');
});

Route::post('consultas/{consulta}/laboratorio', [LaboratorioController::class, 'store'])
    ->middleware('permission:laboratorio.solicitar')->name('laboratorio.store');
Route::middleware('permission:laboratorio.resultados')->group(function () {
    Route::get('laboratorio', [LaboratorioController::class, 'index'])->name('laboratorio.index');
    Route::get('laboratorio/{orden}', [LaboratorioController::class, 'show'])->name('laboratorio.show');
    Route::post('laboratorio/{orden}/resultados', [LaboratorioController::class, 'resultados'])->name('laboratorio.resultados');
    Route::get('laboratorio/resultados/{item}/archivo', [LaboratorioController::class, 'archivo'])->name('laboratorio.archivo');
});
