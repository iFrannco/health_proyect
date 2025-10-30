<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $builder = $this->db->table('roles');

        $now = date('Y-m-d H:i:s');

        $builder->insertBatch([
            [
                'id'         => 1,
                'nombre'     => 'Medico',
                'slug'       => 'medico',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => 2,
                'nombre'     => 'Paciente',
                'slug'       => 'paciente',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}

