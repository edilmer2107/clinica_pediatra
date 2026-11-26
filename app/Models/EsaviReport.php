<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsaviReport extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha_inicio_sintomas' => 'datetime', 'notificado_at' => 'datetime'];
    }

    public function immunization(): BelongsTo
    {
        return $this->belongsTo(Immunization::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por');
    }
}
