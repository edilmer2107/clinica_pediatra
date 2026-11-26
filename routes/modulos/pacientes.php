<?php

use App\Http\Controllers\Pacientes\DesarrolloController;
use App\Http\Controllers\Pacientes\EsaviController;
use App\Http\Controllers\Pacientes\FichaClinicaController;
use App\Http\Controllers\Pacientes\MedicionController;
use App\Http\Controllers\Pacientes\PacienteController;
use App\Http\Controllers\Pacientes\TutorController;
use App\Http\Controllers\Pacientes\VacunacionController;
use Illuminate\Support\Facades\Route;

// Módulo 1 — Gestión de Pacientes Pediátricos
Route::middleware('permission:pacientes.ver')->group(function () {
    Route::get('pacientes', [PacienteController::class, 'index'])->name('pacientes.index');
    Route::get('pacientes/registrar', [PacienteController::class, 'create'])->name('pacientes.create');
    Route::post('pacientes', [PacienteController::class, 'store'])->name('pacientes.store');
    Route::get('pacientes/{paciente}', [PacienteController::class, 'show'])->whereNumber('paciente')->name('pacientes.show');
    Route::get('pacientes/{paciente}/editar', [PacienteController::class, 'edit'])->name('pacientes.edit');
    Route::put('pacientes/{paciente}', [PacienteController::class, 'update'])->name('pacientes.update');

    Route::get('tutores', [TutorController::class, 'index'])->name('tutores.index');
    Route::get('tutores/{tutor}', [TutorController::class, 'show'])->name('tutores.show');
    Route::get('tutores/{tutor}/editar', [TutorController::class, 'edit'])->name('tutores.edit');
    Route::put('tutores/{tutor}', [TutorController::class, 'update'])->name('tutores.update');
    Route::post('tutores/{tutor}/acceso', [TutorController::class, 'crearAcceso'])->name('tutores.acceso');
    Route::post('pacientes/{paciente}/tutores', [TutorController::class, 'vincular'])->name('pacientes.tutores.vincular');
    Route::delete('pacientes/{paciente}/tutores/{tutor}', [TutorController::class, 'desvincular'])->name('pacientes.tutores.desvincular');
    Route::post('pacientes/{paciente}/consentimientos', [TutorController::class, 'consentimiento'])->name('pacientes.consentimientos.store');
    Route::patch('consentimientos/{consentimiento}/revocar', [TutorController::class, 'revocarConsentimiento'])->name('consentimientos.revocar');
});

Route::middleware('permission:ficha.editar')->group(function () {
    Route::get('pacientes/{paciente}/ficha', [FichaClinicaController::class, 'edit'])->name('pacientes.ficha.edit');
    Route::put('pacientes/{paciente}/ficha', [FichaClinicaController::class, 'update'])->name('pacientes.ficha.update');
    Route::post('pacientes/{paciente}/hitos', [DesarrolloController::class, 'guardarHitos'])->name('pacientes.hitos.store');
    Route::get('pacientes/{paciente}/psc35', [DesarrolloController::class, 'createPsc35'])->name('pacientes.psc35.create');
    Route::post('pacientes/{paciente}/psc35', [DesarrolloController::class, 'storePsc35'])->name('pacientes.psc35.store');
});

Route::post('pacientes/{paciente}/mediciones', [MedicionController::class, 'store'])
    ->middleware('permission:mediciones.registrar')->name('pacientes.mediciones.store');

Route::middleware('permission:vacunas.ver')->group(function () {
    Route::get('vacunacion', [VacunacionController::class, 'index'])->name('vacunacion.index');
    Route::post('pacientes/{paciente}/vacunas/recordatorio', [VacunacionController::class, 'recordatorio'])->name('vacunacion.recordatorio');
});
Route::middleware('permission:vacunas.aplicar')->group(function () {
    Route::get('pacientes/{paciente}/vacunas/{dosis}/aplicar', [VacunacionController::class, 'create'])->name('vacunacion.create');
    Route::post('pacientes/{paciente}/vacunas/{dosis}', [VacunacionController::class, 'store'])->name('vacunacion.store');
});

Route::middleware('permission:esavi.registrar')->group(function () {
    Route::get('vacunacion/esavi', [EsaviController::class, 'index'])->name('esavi.index');
    Route::get('vacunacion/inmunizaciones/{inmunizacion}/esavi', [EsaviController::class, 'create'])->name('esavi.create');
    Route::post('vacunacion/inmunizaciones/{inmunizacion}/esavi', [EsaviController::class, 'store'])->name('esavi.store');
    Route::get('vacunacion/esavi/{esavi}', [EsaviController::class, 'show'])->name('esavi.show');
});
