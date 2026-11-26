<?php

namespace App\Models;

use App\Models\Concerns\CodigoCorrelativo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use CodigoCorrelativo;
    use HasFactory, SoftDeletes;

    public const PREFIJO_CODIGO = 'HC';

    public const COLUMNA_CODIGO = 'historia_clinica';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'activo' => 'boolean',
        ];
    }

    public function tutors(): BelongsToMany
    {
        return $this->belongsToMany(Tutor::class)
            ->withPivot(['id', 'parentesco', 'es_principal', 'puede_retirar_medicamentos'])
            ->withTimestamps();
    }

    public function tutorPrincipal(): ?Tutor
    {
        return $this->tutors->firstWhere('pivot.es_principal', true) ?? $this->tutors->first();
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class);
    }

    public function consentimientoVigente(): ?Consent
    {
        return $this->consents()
            ->where('acepta_tratamiento_datos', true)
            ->whereNull('revocado_at')
            ->latest('aceptado_at')
            ->first();
    }

    public function pediatricRecord(): HasOne
    {
        return $this->hasOne(PediatricRecord::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(AnthropometricMeasurement::class)->orderBy('fecha');
    }

    public function milestoneAssessments(): HasMany
    {
        return $this->hasMany(MilestoneAssessment::class);
    }

    public function psc35Screenings(): HasMany
    {
        return $this->hasMany(Psc35Screening::class);
    }

    public function immunizations(): HasMany
    {
        return $this->hasMany(Immunization::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(LabOrder::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(PatientAccount::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function edadEnDias(?Carbon $fecha = null): int
    {
        return (int) $this->fecha_nacimiento->diffInDays($fecha ?? now());
    }

    public function edadEnMeses(?Carbon $fecha = null): int
    {
        return (int) $this->fecha_nacimiento->diffInMonths($fecha ?? now());
    }

    /** Edad legible: "1 a 3 m", "5 m 10 d", "12 d". */
    public function getEdadTextoAttribute(): string
    {
        $diff = $this->fecha_nacimiento->diff(now());
        if ($diff->y > 0) {
            return "{$diff->y} a {$diff->m} m";
        }
        if ($diff->m > 0) {
            return "{$diff->m} m {$diff->d} d";
        }

        return "{$diff->d} d";
    }

}
