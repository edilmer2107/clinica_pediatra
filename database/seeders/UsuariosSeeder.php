<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios de prueba (datos ficticios). Contraseña común: definida en DEMO_PASSWORD.
 */
class UsuariosSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Clinica2026!';

    public function run(): void
    {
        $usuarios = [
            ['Administrador del Sistema', 'admin@clinica.test', 'administrador', '40000001', null, null],
            ['Dra. Lucía Ramírez Torres', 'medico@clinica.test', 'medico', '40000002', 'Pediatría', 'CMP 054321'],
            ['Dr. Carlos Mendoza Ríos', 'medico2@clinica.test', 'medico', '40000003', 'Pediatría', 'CMP 061234'],
            ['Lic. Rosa Huamán Pérez', 'enfermeria@clinica.test', 'enfermeria', '40000004', 'Enfermería', 'CEP 078912'],
            ['Ana Torres Vela', 'recepcion@clinica.test', 'recepcion', '40000005', null, null],
            ['Q.F. Miguel Salazar Díaz', 'farmacia@clinica.test', 'farmaceutico', '40000006', 'Farmacia', 'CQFP 12345'],
            ['Julia Campos Ruiz', 'facturacion@clinica.test', 'facturacion', '40000007', null, null],
        ];

        foreach ($usuarios as [$nombre, $email, $rol, $dni, $especialidad, $colegiatura]) {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $nombre,
                'password' => self::DEMO_PASSWORD,
                'dni' => $dni,
                'telefono' => '9'.substr($dni, 0, 8),
                'especialidad' => $especialidad,
                'colegiatura' => $colegiatura,
                'activo' => true,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$rol]);
        }
    }
}
