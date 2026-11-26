<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Patient;
use App\Services\Pacientes\VacunacionService;
use App\Services\Portal\NotificacionService;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;

#[AsCommand(name: 'clinica:recordatorios', description: 'Envía recordatorios de citas (próximas 24 h) y alertas de vacunas vencidas')]
class EnviarRecordatorios extends Command
{
    protected $signature = 'clinica:recordatorios {--vacunas : También envía alertas de vacunas vencidas}';

    public function handle(NotificacionService $notificaciones, VacunacionService $vacunas): int
    {
        $citas = Appointment::with('patient.tutors', 'doctor')
            ->whereIn('estado', ['programada', 'confirmada'])
            ->whereNull('recordatorio_enviado_at')
            ->whereBetween('fecha_hora', [now(), now()->addDay()])
            ->get();

        $total = 0;
        foreach ($citas as $cita) {
            $total += $notificaciones->recordatorioCita($cita);
        }
        $this->info("Recordatorios de citas: {$citas->count()} citas, {$total} mensajes.");

        if ($this->option('vacunas')) {
            $mensajes = 0;
            foreach (Patient::with('tutors')->where('activo', true)->get() as $paciente) {
                $vencidas = $vacunas->dosisVencidas($paciente);
                if ($vencidas !== []) {
                    $mensajes += $notificaciones->vacunasVencidas($paciente, $vencidas);
                }
            }
            $this->info("Alertas de vacunas vencidas: {$mensajes} mensajes.");
        }

        return self::SUCCESS;
    }
}
