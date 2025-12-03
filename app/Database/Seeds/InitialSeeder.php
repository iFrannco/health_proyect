<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('RoleSeeder');
        $this->call('TipoDiagnosticoSeeder');
        $this->call('EstadoActividadSeeder');
        $this->call('CategoriaActividadSeeder');
        $this->call('EspecialidadSeeder');
        $this->call('UserSeeder');
        $this->call('MedicoDashboardDemoSeeder');
    }
}
