<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationDiagnosis extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['principal' => 'boolean'];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function cie10(): BelongsTo
    {
        return $this->belongsTo(Cie10Code::class, 'cie10_code_id');
    }
}
