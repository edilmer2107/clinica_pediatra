<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Immunization extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha_aplicacion' => 'date'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class);
    }

    public function scheduleDose(): BelongsTo
    {
        return $this->belongsTo(VaccineScheduleDose::class, 'vaccine_schedule_dose_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicado_por');
    }

    public function esaviReports(): HasMany
    {
        return $this->hasMany(EsaviReport::class);
    }
}
