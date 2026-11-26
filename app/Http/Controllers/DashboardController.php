<?php

namespace App\Http\Controllers;

use App\Models\AnthropometricMeasurement;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Lot;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        if ($user->esTutor()) {
            return redirect()->route('portal.index');
        }

        $indicadores = [
            'pacientes' => Patient::count(),
            'citas_hoy' => Appointment::whereDate('fecha_hora', today())->whereIn('estado', ['programada', 'confirmada'])->count(),
            'alertas_crecimiento' => AnthropometricMeasurement::where('tiene_alerta', true)->whereDate('fecha', '>=', today()->subDays(30))->count(),
            'recetas_pendientes' => Prescription::whereIn('estado', ['emitida', 'parcial'])->count(),
            'lotes_por_vencer' => Lot::where('estado', 'activo')->where('cantidad_actual', '>', 0)
                ->whereBetween('fecha_vencimiento', [today(), today()->addDays(config('clinica.dias_alerta_vencimiento'))])->count(),
            'lotes_vencidos' => Lot::where('cantidad_actual', '>', 0)->whereDate('fecha_vencimiento', '<', today())
                ->whereNotIn('estado', ['retirado'])->count(),
            'por_cobrar' => (float) Invoice::whereIn('estado', ['pendiente', 'parcial'])->selectRaw('COALESCE(SUM(total - monto_pagado),0) as s')->value('s'),
        ];

        $proximasCitas = $user->can('citas.ver')
            ? Appointment::with(['patient', 'doctor'])
                ->where('fecha_hora', '>=', now()->startOfDay())
                ->whereIn('estado', ['programada', 'confirmada'])
                ->when($user->hasRole('medico'), fn ($q) => $q->where('doctor_id', $user->id))
                ->orderBy('fecha_hora')->limit(8)->get()
            : collect();

        return view('dashboard.index', compact('indicadores', 'proximasCitas'));
    }
}
