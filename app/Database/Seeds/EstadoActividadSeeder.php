<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EstadoActividadSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            [
                'nombre' => 'Pendiente',
                'slug'   => 'pendiente',
                'orden'  => 1,
            ],
            [
                'nombre' => 'Completada',
                'slug'   => 'completada',
                'orden'  => 2,
            ],
            [
                'nombre' => 'Vencida',
                'slug'   => 'vencida',
                'orden'  => 3,
            ],
        ];

        $now      = date('Y-m-d H:i:s');
        $builder  = $this->db->table('estado_actividad');

        foreach ($estados as $estado) {
            $existing = $builder->where('slug', $estado['slug'])->get()->getFirstRow();

            if ($existing !== null) {
                $builder->where('id', $existing->id)->update([
                    'nombre'     => $estado['nombre'],
                    'orden'      => $estado['orden'],
                    'updated_at' => $now,
                ]);
            } else {
                $builder->insert([
                    'nombre'     => $estado['nombre'],
                    'slug'       => $estado['slug'],
                    'orden'      => $estado['orden'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $builder->resetQuery();
        }
    }
}
