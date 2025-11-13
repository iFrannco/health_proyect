<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'id'     => 1,
                'nombre' => 'Medico',
                'slug'   => 'medico',
            ],
            [
                'id'     => 2,
                'nombre' => 'Paciente',
                'slug'   => 'paciente',
            ],
            [
                'id'     => 3,
                'nombre' => 'Administrador',
                'slug'   => 'admin',
            ],
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($roles as $rol) {
            $builder  = $this->db->table('roles');
            $existing = $builder->where('id', $rol['id'])->get()->getFirstRow();

            if ($existing !== null) {
                $this->db->table('roles')->where('id', $rol['id'])->update([
                    'nombre'     => $rol['nombre'],
                    'slug'       => $rol['slug'],
                    'updated_at' => $now,
                ]);
            } else {
                $this->db->table('roles')->insert([
                    'id'         => $rol['id'],
                    'nombre'     => $rol['nombre'],
                    'slug'       => $rol['slug'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
