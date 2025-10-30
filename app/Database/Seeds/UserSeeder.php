<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = $this->db->table('roles')
            ->select(['id', 'slug'])
            ->get()
            ->getResultArray();

        $rolesBySlug = [];
        foreach ($roles as $role) {
            $rolesBySlug[$role['slug']] = (int) $role['id'];
        }

        $medicoRoleId   = $rolesBySlug['medico'] ?? null;
        $pacienteRoleId = $rolesBySlug['paciente'] ?? null;

        if (! $medicoRoleId || ! $pacienteRoleId) {
            throw new \RuntimeException('Los roles basicos no estan disponibles para el seeding de usuarios.');
        }

        $now = date('Y-m-d H:i:s');

        $usuarios = [
            [
                'nombre'        => 'Ana',
                'apellido'      => 'Medina',
                'email'         => 'ana.medina@example.com',
                'password_hash' => password_hash('Medico123!', PASSWORD_BCRYPT),
                'role_id'       => $medicoRoleId,
                'activo'        => 1,
                'fecha_nac'     => '1980-03-12',
                'telefono'      => '+54-11-5555-1001',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'nombre'        => 'Luis',
                'apellido'      => 'Paz',
                'email'         => 'luis.paz@example.com',
                'password_hash' => password_hash('Paciente123!', PASSWORD_BCRYPT),
                'role_id'       => $pacienteRoleId,
                'activo'        => 1,
                'fecha_nac'     => '1990-07-25',
                'telefono'      => '+54-11-5555-1002',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'nombre'        => 'Julieta',
                'apellido'      => 'Rossi',
                'email'         => 'julieta.rossi@example.com',
                'password_hash' => password_hash('Paciente123!', PASSWORD_BCRYPT),
                'role_id'       => $pacienteRoleId,
                'activo'        => 1,
                'fecha_nac'     => '1988-11-05',
                'telefono'      => '+54-11-5555-1003',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        $this->db->table('users')->insertBatch($usuarios);
    }
}

