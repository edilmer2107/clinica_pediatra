<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'dni', 'telefono', 'especialidad', 'colegiatura', 'activo', 'ultimo_acceso_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const ROLES = [
        'administrador' => 'Administrador',
        'medico' => 'Médico pediatra',
        'enfermeria' => 'Enfermería',
        'recepcion' => 'Recepción',
        'farmaceutico' => 'Farmacéutico',
        'facturacion' => 'Facturación',
        'tutor' => 'Tutor',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function tutor(): HasOne
    {
        return $this->hasOne(Tutor::class);
    }

    public function esTutor(): bool
    {
        return $this->hasRole('tutor');
    }

    public function rolPrincipal(): string
    {
        $rol = $this->getRoleNames()->first();

        return self::ROLES[$rol] ?? 'Sin rol';
    }
}
