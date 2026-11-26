<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PediatricRecord extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'suplemento_hierro_acido_folico' => 'boolean',
            'tamizaje_neonatal' => 'boolean',
            'peso_nacer_kg' => 'float',
            'talla_nacer_cm' => 'float',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
