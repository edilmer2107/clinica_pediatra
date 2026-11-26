<?php

namespace App\Models;

use App\Models\Concerns\CodigoCorrelativo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prescription extends Model
{
    use CodigoCorrelativo;

    public const PREFIJO_CODIGO = 'RX';

    public const COLUMNA_CODIGO = 'codigo';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'peso_kg' => 'float',
            'talla_cm' => 'float',
            'superficie_corporal' => 'float',
            'lista_para_retiro_at' => 'datetime',
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

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function dispensations(): HasMany
    {
        return $this->hasMany(Dispensation::class);
    }

}
