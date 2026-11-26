<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    protected $guarded = ['id'];

    public const ESTADOS = [
        'programada' => 'Programada',
        'confirmada' => 'Confirmada',
        'atendida' => 'Atendida',
        'cancelada' => 'Cancelada',
        'no_asistio' => 'No asistió',
        'reprogramada' => 'Reprogramada',
    ];

    public const SERVICIOS = [
        'general' => 'Consulta general',
        'control_nino_sano' => 'Control de niño sano',
        'telemedicina' => 'Telemedicina',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'cancelada_at' => 'datetime',
            'recordatorio_enviado_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    public function reprogramadaDesde(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'reprogramada_desde_id');
    }

    public function esModificable(): bool
    {
        return in_array($this->estado, ['programada', 'confirmada'], true);
    }
}
