<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TipoDiagnosticoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'id'          => 1,
                'nombre'     => 'Consulta inicial',
                'slug'       => 'consulta-inicial',
                'descripcion'=> 'Evaluacion inicial del paciente para establecer diagnostico.',
            ],
            [
                'id'          => 2,
                'nombre'     => 'Seguimiento',
                'slug'       => 'seguimiento',
                'descripcion'=> 'Control de evolucion o ajuste de tratamiento.',
            ],
            [
                'id'          => 3,
                'nombre'     => 'Tratamiento',
                'slug'       => 'tratamiento',
                'descripcion'=> 'Diagnostico asociado a intervencion terapeutica.',
            ],
        ];

        $now = date('Y-m-d H:i:s');

        foreach ($tipos as $tipo) {
            $builder  = $this->db->table('tipos_diagnostico');
            $existing = $builder->where('id', $tipo['id'])->get()->getFirstRow();

            if ($existing !== null) {
                $this->db->table('tipos_diagnostico')
                    ->where('id', $tipo['id'])
                    ->update([
                        'nombre'      => $tipo['nombre'],
                        'slug'        => $tipo['slug'],
                        'descripcion' => $tipo['descripcion'],
                        'updated_at'  => $now,
                    ]);
            } else {
                $this->db->table('tipos_diagnostico')->insert([
                    'id'          => $tipo['id'],
                    'nombre'      => $tipo['nombre'],
                    'slug'        => $tipo['slug'],
                    'descripcion' => $tipo['descripcion'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }
}
