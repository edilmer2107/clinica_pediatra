<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recordatorios automáticos de citas (cada hora) y alertas de vacunas vencidas (semanal)
Schedule::command('clinica:recordatorios')->hourly();
Schedule::command('clinica:recordatorios --vacunas')->weeklyOn(1, '09:00');
