<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategoriaActividadSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'id'          => 1,
                'nombre'      => 'Otras / Genérica',
                'descripcion' => 'Usar cuando ninguna categoría específica aplica.',
                'color_hex'   => '#6c757d',
                'activo'      => 1,
            ],
            [
                'id'          => 2,
                'nombre'      => 'Educación sanitaria',
                'descripcion' => 'Material educativo, indicaciones e información al paciente.',
                'color_hex'   => '#17a2b8',
                'activo'      => 1,
            ],
            [
                'id'          => 3,
                'nombre'      => 'Medicaciones',
                'descripcion' => 'Administración o seguimiento de fármacos.',
                'color_hex'   => '#007bff',
                'activo'      => 1,
            ],
            [
                'id'          => 4,
                'nombre'      => 'Ejercicio / Indicaciones',
                'descripcion' => 'Rutinas físicas, indicaciones de hábitos o autocuidado.',
                'color_hex'   => '#28a745',
                'activo'      => 1,
            ],
            [
                'id'          => 5,
                'nombre'      => 'Controles y seguimiento',
                'descripcion' => 'Controles clínicos, turnos o mediciones programadas.',
                'color_hex'   => '#ffc107',
                'activo'      => 1,
            ],
        ];

        $now = date('Y-m-d H:i:s');
        $builder = $this->db->table('categoria_actividad');

        foreach ($categorias as $categoria) {
            $existente = $builder
                ->select('id')
                ->where('id', $categoria['id'])
                ->get()
                ->getFirstRow();

            if ($existente !== null) {
                $builder->where('id', $categoria['id'])->update([
                    'nombre'      => $categoria['nombre'],
                    'descripcion' => $categoria['descripcion'],
                    'color_hex'   => $categoria['color_hex'],
                    'activo'      => $categoria['activo'],
                    'updated_at'  => $now,
                ]);
            } else {
                $builder->insert([
                    'id'          => $categoria['id'],
                    'nombre'      => $categoria['nombre'],
                    'descripcion' => $categoria['descripcion'],
                    'color_hex'   => $categoria['color_hex'],
                    'activo'      => $categoria['activo'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }

            $builder->resetQuery();
        }
    }
}
