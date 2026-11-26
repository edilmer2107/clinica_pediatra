<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Psc35Screening extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'respuestas' => 'array', 'positivo' => 'boolean'];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicado_por');
    }
}
