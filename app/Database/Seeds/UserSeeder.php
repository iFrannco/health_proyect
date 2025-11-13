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

        $adminRoleId    = $rolesBySlug['admin'] ?? null;
        $medicoRoleId   = $rolesBySlug['medico'] ?? null;
        $pacienteRoleId = $rolesBySlug['paciente'] ?? null;

        if (! $adminRoleId || ! $medicoRoleId || ! $pacienteRoleId) {
            throw new \RuntimeException('Los roles basicos no estan disponibles para el seeding de usuarios.');
        }

        $now = date('Y-m-d H:i:s');

        $usuarios = [
            [
                'nombre'    => 'Rocio',
                'apellido'  => 'Fernandez',
                'email'     => 'admin@healthpro.test',
                'password'  => 'Admin123!',
                'role_id'   => $adminRoleId,
                'activo'    => 1,
                'fecha_nac' => '1982-01-15',
                'telefono'  => '+54-11-5555-0999',
            ],
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
            [
                'nombre'    => 'Mariano',
                'apellido'  => 'Quiroga',
                'email'     => 'mariano.quiroga@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1985-02-14',
                'telefono'  => '+54-11-5555-1004',
            ],
            [
                'nombre'    => 'Carla',
                'apellido'  => 'Benitez',
                'email'     => 'carla.benitez@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1992-09-21',
                'telefono'  => '+54-11-5555-1005',
            ],
            [
                'nombre'    => 'Rafael',
                'apellido'  => 'Delgado',
                'email'     => 'rafael.delgado@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 0,
                'fecha_nac' => '1979-06-02',
                'telefono'  => '+54-11-5555-1006',
            ],
            [
                'nombre'    => 'Lucia',
                'apellido'  => 'Mendez',
                'email'     => 'lucia.mendez@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1995-12-18',
                'telefono'  => '+54-11-5555-1007',
            ],
            [
                'nombre'    => 'Gaston',
                'apellido'  => 'Saavedra',
                'email'     => 'gaston.saavedra@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1983-04-30',
                'telefono'  => '+54-11-5555-1008',
            ],
            [
                'nombre'    => 'Melina',
                'apellido'  => 'Ponce',
                'email'     => 'melina.ponce@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1997-08-11',
                'telefono'  => '+54-11-5555-1009',
            ],
            [
                'nombre'    => 'Sergio',
                'apellido'  => 'Lagos',
                'email'     => 'sergio.lagos@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1986-01-09',
                'telefono'  => '+54-11-5555-1010',
            ],
            [
                'nombre'    => 'Patricia',
                'apellido'  => 'Ortega',
                'email'     => 'patricia.ortega@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1975-07-16',
                'telefono'  => '+54-11-5555-1011',
            ],
            [
                'nombre'    => 'Mauricio',
                'apellido'  => 'Herrera',
                'email'     => 'mauricio.herrera@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1991-10-23',
                'telefono'  => '+54-11-5555-1012',
            ],
            [
                'nombre'    => 'Daniela',
                'apellido'  => 'Vega',
                'email'     => 'daniela.vega@example.com',
                'password'  => 'Paciente123!',
                'role_id'   => $pacienteRoleId,
                'activo'    => 1,
                'fecha_nac' => '1989-05-05',
                'telefono'  => '+54-11-5555-1013',
            ],
        ];

        $dniSecuencia = 20000001;
        foreach ($usuarios as &$usuario) {
            $usuario['dni'] = (string) ($dniSecuencia++);
        }
        unset($usuario);

        foreach ($usuarios as $usuario) {
            $usuarioExistente = $this->db->table('users')
                ->where('email', $usuario['email'])
                ->get()
                ->getFirstRow();

            $payload = [
                'nombre'        => $usuario['nombre'],
                'apellido'      => $usuario['apellido'],
                'dni'           => $usuario['dni'],
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
