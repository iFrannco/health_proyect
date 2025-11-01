<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EstadoActividadSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            [
                'id'     => 1,
                'nombre' => 'Sin iniciar',
                'slug'   => 'sin_iniciar',
                'orden'  => 1,
            ],
            [
                'id'     => 2,
                'nombre' => 'Iniciada',
                'slug'   => 'iniciada',
                'orden'  => 2,
            ],
            [
                'id'     => 3,
                'nombre' => 'Terminada',
                'slug'   => 'terminada',
                'orden'  => 3,
            ],
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($estados as $estado) {
            $builder  = $this->db->table('estado_actividad');
            $existing = $builder->where('id', $estado['id'])->get()->getFirstRow();

            if ($existing !== null) {
                $this->db->table('estado_actividad')
                    ->where('id', $estado['id'])
                    ->update([
                        'nombre'     => $estado['nombre'],
                        'slug'       => $estado['slug'],
                        'orden'      => $estado['orden'],
                        'updated_at' => $now,
                    ]);
            } else {
                $this->db->table('estado_actividad')->insert([
                    'id'         => $estado['id'],
                    'nombre'     => $estado['nombre'],
                    'slug'       => $estado['slug'],
                    'orden'      => $estado['orden'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
