<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientAccount extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['abierta_at' => 'datetime', 'cerrada_at' => 'datetime'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    public function cargosPendientes(): HasMany
    {
        return $this->hasMany(Charge::class)->whereNull('invoice_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
