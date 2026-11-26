<?php

namespace App\Models;

use App\Models\Concerns\CodigoCorrelativo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use CodigoCorrelativo;

    public const PREFIJO_CODIGO = 'B001';

    public const COLUMNA_CODIGO = 'numero';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'anulada_at' => 'datetime',
            'subtotal_servicios' => 'float',
            'subtotal_medicamentos' => 'float',
            'subtotal' => 'float',
            'igv' => 'float',
            'total' => 'float',
            'monto_pagado' => 'float',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PatientAccount::class, 'patient_account_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(Tutor::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function saldo(): float
    {
        return round($this->total - $this->monto_pagado, 2);
    }
}
