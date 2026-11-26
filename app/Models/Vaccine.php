<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vaccine extends Model
{
    protected $guarded = ['id'];

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function scheduleDoses(): HasMany
    {
        return $this->hasMany(VaccineScheduleDose::class)->orderBy('orden');
    }

    public function immunizations(): HasMany
    {
        return $this->hasMany(Immunization::class);
    }
}
