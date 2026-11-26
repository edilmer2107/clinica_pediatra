<?php

namespace App\Models;

use App\Models\Concerns\CodigoCorrelativo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabOrder extends Model
{
    use CodigoCorrelativo;

    public const PREFIJO_CODIGO = 'OL';

    public const COLUMNA_CODIGO = 'codigo';

    protected $guarded = ['id'];

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
        return $this->hasMany(LabOrderItem::class);
    }

    public function tieneAnormales(): bool
    {
        return $this->items->contains('es_anormal', true);
    }

}
