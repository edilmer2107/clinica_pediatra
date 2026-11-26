<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $guarded = ['id'];

    public const ESTADOS = [
        'activo' => 'Activo',
        'cuarentena' => 'Cuarentena',
        'retirado' => 'Retirado',
        'agotado' => 'Agotado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date',
            'fecha_ingreso' => 'date',
            'costo_unitario' => 'float',
        ];
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function estaVencido(): bool
    {
        return $this->fecha_vencimiento->lt(today());
    }

    public function esDispensable(): bool
    {
        return $this->estado === 'activo' && ! $this->estaVencido() && $this->cantidad_actual > 0;
    }

    public function diasParaVencer(): int
    {
        return (int) today()->diffInDays($this->fecha_vencimiento, false);
    }
}
