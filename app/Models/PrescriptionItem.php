<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'alerta_dosis' => 'boolean',
            'alerta_interaccion' => 'boolean',
            'dosis_indicada' => 'float',
            'dosis_diaria_mg' => 'float',
            'dosis_por_toma_mg' => 'float',
            'volumen_por_toma_ml' => 'float',
        ];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function cantidadPendiente(): int
    {
        return max(0, $this->cantidad - $this->cantidad_dispensada);
    }
}
