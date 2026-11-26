<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneAssessment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha' => 'date', 'logrado' => 'boolean', 'alerta' => 'boolean'];
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
