<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Charge extends Model
{
    protected $guarded = ['id'];

    public const TIPOS_SERVICIO = ['consulta', 'procedimiento', 'vacuna', 'laboratorio'];

    protected function casts(): array
    {
        return ['cantidad' => 'float', 'precio_unitario' => 'float', 'subtotal' => 'float'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PatientAccount::class, 'patient_account_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function origen(): MorphTo
    {
        return $this->morphTo();
    }
}
