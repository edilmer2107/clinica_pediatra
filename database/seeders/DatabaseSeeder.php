<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermisosSeeder::class,
            UsuariosSeeder::class,
            FarmaciaSeeder::class,
            PacientesCatalogoSeeder::class,
            ClinicaCatalogoSeeder::class,
            PacientesDemoSeeder::class,
            ClinicaDemoSeeder::class,
        ]);
    }
}
