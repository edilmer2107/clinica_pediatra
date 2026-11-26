<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnthropometricMeasurement extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'alertas' => 'array',
            'tiene_alerta' => 'boolean',
            'peso_kg' => 'float',
            'talla_cm' => 'float',
            'perimetro_cefalico_cm' => 'float',
            'imc' => 'float',
            'z_peso_edad' => 'float',
            'z_talla_edad' => 'float',
            'z_pc_edad' => 'float',
            'z_imc_edad' => 'float',
            'p_peso_edad' => 'float',
            'p_talla_edad' => 'float',
            'p_pc_edad' => 'float',
            'p_imc_edad' => 'float',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
