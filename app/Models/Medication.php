<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'es_controlado' => 'boolean',
            'es_vacuna' => 'boolean',
            'activo' => 'boolean',
            'precio_venta' => 'float',
            'mg_por_unidad' => 'float',
            'mg_por_ml' => 'float',
            'dosis_min_mg_kg_dia' => 'float',
            'dosis_max_mg_kg_dia' => 'float',
            'dosis_max_diaria_mg' => 'float',
            'dosis_min_mg_m2_dia' => 'float',
            'dosis_max_mg_m2_dia' => 'float',
        ];
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    /** Lotes dispensables: activos, con stock y no vencidos. */
    public function lotsDisponibles(): HasMany
    {
        return $this->hasMany(Lot::class)
            ->where('estado', 'activo')
            ->where('cantidad_actual', '>', 0)
            ->whereDate('fecha_vencimiento', '>=', today());
    }

    public function stockTotal(): int
    {
        return (int) $this->lotsDisponibles()->sum('cantidad_actual');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->concentracion} ({$this->forma_farmaceutica})");
    }
}
