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
                'nombre'    => 'Ana',
                'apellido'  => 'Medina',
                'email'     => 'ana.medina@example.com',
                'password'  => 'Medico123!',
                'role_id'   => $medicoRoleId,
                'activo'    => 1,
                'fecha_nac' => '1980-03-12',
                'telefono'  => '+54-11-5555-1001',
            ],
            [
                'nombre'    => 'Luis',
                'apellido'  => 'Paz',
                'email'     => 'luis.paz@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1990-07-25',
                'telefono'  => '+54-11-5555-1002',
            ],
            [
                'nombre'    => 'Julieta',
                'apellido'  => 'Rossi',
                'email'     => 'julieta.rossi@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1988-11-05',
                'telefono'  => '+54-11-5555-1003',
            ],
        ];

        foreach ($usuarios as $usuario) {
            $usuarioExistente = $this->db->table('users')
                ->where('email', $usuario['email'])
                ->get()
                ->getFirstRow();

            $payload = [
                'nombre'        => $usuario['nombre'],
                'apellido'      => $usuario['apellido'],
                'email'         => $usuario['email'],
                'password_hash' => password_hash($usuario['password'], PASSWORD_BCRYPT),
                'role_id'       => $usuario['role_id'],
                'activo'        => $usuario['activo'],
                'fecha_nac'     => $usuario['fecha_nac'],
                'telefono'      => $usuario['telefono'],
                'updated_at'    => $now,
            ];

            if ($usuarioExistente !== null) {
                $payload['created_at'] = $usuarioExistente->created_at ?? $now;

                $this->db->table('users')
                    ->where('id', $usuarioExistente->id)
                    ->update($payload);
            } else {
                $payload['created_at'] = $now;
                $this->db->table('users')->insert($payload);
            }
        }
    }
}
