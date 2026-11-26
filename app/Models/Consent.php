<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'acepta_tratamiento_datos' => 'boolean',
            'acepta_atencion_medica' => 'boolean',
            'acepta_notificaciones' => 'boolean',
            'aceptado_at' => 'datetime',
            'revocado_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }
}
