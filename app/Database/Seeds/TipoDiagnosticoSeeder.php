<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TipoDiagnosticoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            [
                'nombre'     => 'Consulta inicial',
                'slug'       => 'consulta-inicial',
                'descripcion'=> 'Evaluacion inicial del paciente para establecer diagnostico.',
            ],
            [
                'nombre'     => 'Seguimiento',
                'slug'       => 'seguimiento',
                'descripcion'=> 'Control de evolucion o ajuste de tratamiento.',
            ],
            [
                'nombre'     => 'Tratamiento',
                'slug'       => 'tratamiento',
                'descripcion'=> 'Diagnostico asociado a intervencion terapeutica.',
            ],
        ];

        $now = date('Y-m-d H:i:s');

        $tipos = array_map(static function (array $tipo) use ($now): array {
            return array_merge($tipo, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $tipos);

        $this->db->table('tipos_diagnostico')->insertBatch($tipos);
    }
}

